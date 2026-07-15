<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Department;
use App\Models\Item;
use App\Models\Request as ItemRequest;
use App\Models\StockIn;
use App\Models\StockOpname;
use App\Models\StockOut;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Pencarian global — menelusuri seluruh menu/entitas dalam satu tempat,
 * dibatasi oleh hak akses (role) pengguna, sejalan dengan sidebar.
 */
class SearchController extends Controller
{
    private const LIMIT = 6; // maksimum hasil per kelompok

    public function index(Request $request)
    {
        $q      = trim((string) $request->input('q', ''));
        $role   = auth()->user()->role;
        $groups = [];

        if ($q !== '') {
            foreach ($this->sources($q) as $source) {
                if (! in_array('*', $source['roles'], true) && ! in_array($role, $source['roles'], true)) {
                    continue;
                }
                if (! empty($source['items'])) {
                    $groups[] = $source;
                }
            }
        }

        $total = array_sum(array_map(fn ($g) => count($g['items']), $groups));

        return view('search.index', compact('q', 'groups', 'total'));
    }

    /** Semua sumber pencarian beserta hasilnya untuk kata kunci $q. */
    private function sources(string $q): array
    {
        $like = like_contains($q);
        $user = auth()->user();

        /* ── Barang ── */
        $items = Item::with('category')
            ->where(fn ($w) => $w->where('name', 'like', $like)->orWhere('code', 'like', $like)
                ->orWhere('location', 'like', $like))
            ->limit(self::LIMIT)->get()
            ->map(fn ($i) => [
                'title'    => $i->name,
                'subtitle' => $i->code . ' · ' . ($i->category->name ?? 'Tanpa kategori') . ' · stok ' . $i->stock . ' ' . $i->unit,
                'url'      => route('barang.index', ['search' => $i->code]),
            ])->all();

        /* ── Permintaan Barang ── */
        $requests = ItemRequest::with(['department', 'requester'])
            // Kepala Bidang hanya boleh melihat permintaan bidangnya sendiri —
            // sama dengan pembatasan di halaman indeks permintaan.
            ->when($user->role === 'kepala_bidang',
                fn ($q2) => $q2->where('department_id', $user->department_id))
            ->where(fn ($w) => $w->where('request_no', 'like', $like)
                ->orWhere('note', 'like', $like)
                ->orWhereHas('requester', fn ($r) => $r->where('name', 'like', $like))
                ->orWhereHas('department', fn ($d) => $d->where('name', 'like', $like)))
            ->latest('request_date')->limit(self::LIMIT)->get()
            ->map(fn ($r) => [
                'title'    => $r->request_no,
                'subtitle' => ($r->department->name ?? '-') . ' · ' . ($r->requester->name ?? '-') . ' · ' . ucfirst(str_replace('_', ' ', $r->status)),
                'url'      => route('permintaan.show', $r),
            ])->all();

        /* ── Barang Masuk ── */
        $stockIns = StockIn::with('supplier')
            ->where(fn ($w) => $w->where('transaction_no', 'like', $like)
                ->orWhere('note', 'like', $like)
                ->orWhereHas('supplier', fn ($s) => $s->where('name', 'like', $like)))
            ->latest('date')->limit(self::LIMIT)->get()
            ->map(fn ($s) => [
                'title'    => $s->transaction_no,
                'subtitle' => ($s->supplier->name ?? '-') . ' · ' . optional($s->date)->isoFormat('D MMM YYYY'),
                'url'      => route('barang-masuk.show', $s),
            ])->all();

        /* ── Distribusi / Barang Keluar ── */
        $stockOuts = StockOut::with(['item', 'department'])
            ->where(fn ($w) => $w->where('transaction_no', 'like', $like)
                ->orWhereHas('item', fn ($i) => $i->where('name', 'like', $like))
                ->orWhereHas('department', fn ($d) => $d->where('name', 'like', $like)))
            ->latest('date')->limit(self::LIMIT)->get()
            ->map(fn ($so) => [
                'title'    => $so->transaction_no,
                'subtitle' => ($so->item->name ?? '-') . ' ×' . $so->quantity
                              . ' · ' . ($so->department->name ?? '-')
                              . ' · ' . optional($so->date)->isoFormat('D MMM YYYY'),
                // Detail distribusi menempel pada permintaan asalnya.
                'url'      => $so->request_id ? route('permintaan.show', $so->request_id) : route('distribusi.index'),
            ])->all();

        /* ── Stock Opname ── */
        $opnames = StockOpname::with('createdBy')
            ->where(fn ($w) => $w->where('opname_no', 'like', $like)->orWhere('note', 'like', $like))
            ->latest('date')->limit(self::LIMIT)->get()
            ->map(fn ($o) => [
                'title'    => $o->opname_no,
                'subtitle' => optional($o->date)->isoFormat('D MMM YYYY')
                              . ' · ' . $o->items_count . ' barang dicek, ' . $o->adjusted_count . ' disesuaikan'
                              . ' · ' . ($o->createdBy->name ?? '-'),
                'url'      => route('stock-opname.show', $o),
            ])->all();

        /* ── Pengguna ── */
        $users = User::with('department')
            ->where(fn ($w) => $w->where('name', 'like', $like)->orWhere('email', 'like', $like))
            ->limit(self::LIMIT)->get()
            ->map(fn ($u) => [
                'title'    => $u->name,
                'subtitle' => $u->email . ' · ' . $u->roleLabel() . ($u->department ? ' · ' . $u->department->name : ''),
                'url'      => route('pengguna.index', ['search' => $u->name]),
            ])->all();

        /* ── Kategori ── */
        $categories = Category::where('name', 'like', $like)->orWhere('description', 'like', $like)
            ->limit(self::LIMIT)->get()
            ->map(fn ($c) => [
                'title'    => $c->name,
                'subtitle' => $c->description ?: 'Kategori barang',
                'url'      => route('kategori.index'),
            ])->all();

        /* ── Bidang ── */
        $departments = Department::where('name', 'like', $like)->orWhere('code', 'like', $like)
            ->limit(self::LIMIT)->get()
            ->map(fn ($d) => [
                'title'    => $d->name,
                'subtitle' => ($d->code ? $d->code . ' · ' : '') . 'Bidang / unit kerja',
                'url'      => route('bidang.index'),
            ])->all();

        /* ── Supplier ── */
        $suppliers = Supplier::where(fn ($w) => $w->where('name', 'like', $like)
            ->orWhere('phone', 'like', $like)->orWhere('email', 'like', $like))
            ->limit(self::LIMIT)->get()
            ->map(fn ($s) => [
                'title'    => $s->name,
                'subtitle' => trim(($s->phone ?: '') . ($s->email ? ' · ' . $s->email : ''), ' ·') ?: 'Pemasok',
                'url'      => route('supplier.index', ['search' => $s->name]),
            ])->all();

        return [
            // Selaras dengan menu & route: kepala_bidang tidak punya akses Data Barang.
            ['key' => 'barang',       'label' => 'Data Barang',        'icon' => '☷', 'roles' => ['admin', 'petugas_gudang', 'kasubag_umum', 'pimpinan'], 'items' => $items, 'allUrl' => route('barang.index', ['search' => $q])],
            ['key' => 'permintaan',   'label' => 'Permintaan Barang',  'icon' => '✉', 'roles' => ['*'],                        'items' => $requests,    'allUrl' => route('permintaan.index', ['search' => $q])],
            ['key' => 'barang-masuk', 'label' => 'Barang Masuk',       'icon' => '↓', 'roles' => ['admin', 'petugas_gudang'], 'items' => $stockIns,    'allUrl' => route('barang-masuk.index', ['search' => $q])],
            ['key' => 'distribusi',   'label' => 'Distribusi / Barang Keluar', 'icon' => '↑', 'roles' => ['admin', 'petugas_gudang'], 'items' => $stockOuts, 'allUrl' => route('distribusi.index')],
            ['key' => 'stock-opname', 'label' => 'Stock Opname',       'icon' => '☑', 'roles' => ['admin', 'petugas_gudang'], 'items' => $opnames,     'allUrl' => route('stock-opname.index')],
            ['key' => 'pengguna',     'label' => 'Pengguna',           'icon' => '◯', 'roles' => ['admin'],                    'items' => $users,       'allUrl' => route('pengguna.index', ['search' => $q])],
            ['key' => 'kategori',     'label' => 'Kategori',           'icon' => '⊟', 'roles' => ['admin'],                    'items' => $categories,  'allUrl' => route('kategori.index')],
            ['key' => 'bidang',       'label' => 'Bidang',             'icon' => '⊞', 'roles' => ['admin'],                    'items' => $departments, 'allUrl' => route('bidang.index')],
            ['key' => 'supplier',     'label' => 'Supplier',           'icon' => '⊡', 'roles' => ['admin'],                    'items' => $suppliers,   'allUrl' => route('supplier.index', ['search' => $q])],
        ];
    }
}
