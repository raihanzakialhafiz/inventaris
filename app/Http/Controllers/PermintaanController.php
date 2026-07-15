<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePermintaanRequest;
use App\Models\AuditLog;
use App\Models\Department;
use App\Models\Item;
use App\Models\Request as ItemRequest;
use App\Models\RequestDetail;
use App\Models\User;
use App\Services\DepartmentQuotaService;
use App\Support\Notifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PermintaanController extends Controller
{
    public function __construct(private DepartmentQuotaService $quota)
    {
    }

    public function index(Request $request)
    {
        $user  = auth()->user();
        $query = ItemRequest::with(['department', 'requester', 'approver', 'details.item'])->latest();

        // KaBid hanya lihat permintaan bidangnya
        if ($user->role === 'kepala_bidang') {
            $query->where('department_id', $user->department_id);
        }

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where('request_no', 'like', like_contains($s));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('dept') && $user->role !== 'kepala_bidang') {
            $query->where('department_id', $request->dept);
        }
        if ($request->filled('flagged')) {
            $query->where('is_flagged', true);
        }
        // Submenu (mis. Kasubag): "masuk" = perlu diproses (pending); "diproses" = sudah diproses.
        if ($request->filled('view')) {
            $request->view === 'diproses'
                ? $query->where('status', '!=', 'pending')
                : $query->where('status', 'pending');
        }

        $sortable = ['request_no', 'request_date', 'status', 'created_at'];
        $sort     = in_array($request->sort, $sortable) ? $request->sort : 'created_at';
        $dir      = $request->dir === 'asc' ? 'asc' : 'desc';
        $perPage  = in_array((int)$request->per_page, [10, 25, 50, 100]) ? (int)$request->per_page : 20;

        $permintaan  = $query->orderBy($sort, $dir)->paginate($perPage)->withQueryString();
        $departments = Department::orderBy('name')->get();
        $items       = Item::orderBy('name')->get();
        $itemOptions = $this->buildItemOptions($items, $user);

        return view('permintaan.index', compact('permintaan', 'departments', 'items', 'itemOptions'));
    }

    public function store(StorePermintaanRequest $request)
    {
        $user = auth()->user();

        // Permintaan selalu atas nama bidang — akun tanpa bidang tidak bisa mengajukan.
        if (! $user->department_id) {
            return back()->withInput()
                ->with('error', 'Akun Anda tidak terhubung ke bidang mana pun sehingga tidak dapat mengajukan permintaan.');
        }

        // Evaluasi terhadap kuota bidang (RequestQuota + heuristik fallback).
        $eval = $this->quota->evaluate($user->department_id, $request->items);

        // Kebijakan "block": permintaan ditolak langsung.
        if (! empty($eval['blocked'])) {
            return back()->withInput()
                ->with('error', 'Permintaan ditolak kebijakan kuota — ' . implode(' ', $eval['blocked']));
        }

        // Kebijakan "warn"/over-request: wajib justifikasi (divalidasi server).
        $isFlagged = $eval['flagged'];
        if ($isFlagged) {
            $request->validate(
                ['justification' => 'required|string|min:5'],
                ['justification.required' => 'Justifikasi wajib diisi karena permintaan melebihi batas kuota bidang.',
                 'justification.min'      => 'Justifikasi minimal 5 karakter.']
            );
        }

        $prm = DB::transaction(function () use ($request, $user, $isFlagged) {
            $month = now()->format('ym');
            // lockForUpdate menyerialisasi penomoran agar tidak duplikat saat submit bersamaan.
            $last  = ItemRequest::where('request_no', 'like', "PRM-{$month}-%")->lockForUpdate()->count() + 1;
            $no    = "PRM-{$month}-" . str_pad($last, 3, '0', STR_PAD_LEFT);

            $prm = ItemRequest::create([
                'request_no'    => $no,
                'user_id'       => $user->id,
                'department_id' => $user->department_id,
                'request_date'  => today(),
                'status'        => 'pending',
                'is_flagged'    => $isFlagged,
                'justification' => $request->justification,
                'note'          => $request->note,
            ]);

            foreach ($request->items as $line) {
                RequestDetail::create([
                    'request_id'         => $prm->id,
                    'item_id'            => $line['item_id'],
                    'quantity_requested' => $line['qty'],
                ]);
            }

            AuditLog::create([
                'user_id'     => $user->id,
                'activity'    => 'create_request',
                'entity_type' => 'requests',
                'entity_id'   => $prm->id,
                'ip_address'  => request()->ip(),
            ]);

            return $prm;
        });

        // Notifikasi in-app + email ke seluruh Kasubag aktif (setelah commit).
        $kasubag = User::where('role', 'kasubag_umum')->where('is_active', true)->get();
        Notifier::toUsers(
            $kasubag,
            'new_request',
            "Permintaan baru {$prm->request_no} dari {$user->department->name}" . ($isFlagged ? ' ⚠ (Over-Request)' : ''),
            $prm->id,
            'requests',
            route('permintaan.index'),
            'Lihat Permintaan',
        );

        return redirect()->route('permintaan.index')->with('success', 'Permintaan berhasil diajukan.');
    }

    /**
     * Opsi barang untuk dropdown + konteks kuota (agar form bisa memunculkan
     * justifikasi / peringatan blokir secara akurat bagi Kepala Bidang).
     */
    private function buildItemOptions($items, User $user): \Illuminate\Support\Collection
    {
        $quotaCtx = ($user->role === 'kepala_bidang' && $user->department_id)
            ? $this->quota->itemUsage($user->department_id)->keyBy(fn ($r) => $r['item']->id)
            : collect();

        return $items->map(function (Item $i) use ($quotaCtx) {
            $ctx = $quotaCtx->get($i->id);

            return [
                'value'      => (string) $i->id,
                'label'      => $i->code . ' — ' . $i->name . ' (stok: ' . $i->stock . ' ' . $i->unit . ')'
                                . ($i->stock <= 0 ? ' ✕ Habis' : ''),
                'disabled'   => $i->stock <= 0,
                // Konteks kuota untuk validasi sisi klien (server tetap otoritatif).
                'used'       => $ctx['total'] ?? 0,
                'limit'      => $ctx['limit'] ?? (int) floor($i->monthlyQuota() * 1.5),
                'policy'     => $ctx['policy'] ?? 'warn',
                'configured' => $ctx['configured'] ?? false,
            ];
        })->values();
    }

    public function show(ItemRequest $permintaan)
    {
        $user = auth()->user();

        // KaBid hanya bisa lihat permintaan bidangnya
        if ($user->role === 'kepala_bidang' && $permintaan->department_id !== $user->department_id) {
            abort(403);
        }

        $permintaan->load(['department', 'requester', 'approver', 'details.item.category', 'stockOuts.item']);
        return view('permintaan.show', compact('permintaan', 'user'));
    }
}
