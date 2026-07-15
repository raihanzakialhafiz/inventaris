<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Item;
use App\Models\Request as ItemRequest;
use App\Models\StockIn;
use App\Models\StockOut;
use App\Models\User;
use App\Services\DepartmentQuotaService;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function __construct(private DepartmentQuotaService $quota)
    {
    }

    public function index()
    {
        $user   = Auth::user();
        $period = now()->format('Y-m');

        return match($user->role) {
            'admin', 'pimpinan' => $this->adminDashboard($period),
            'kepala_bidang'     => $this->kabidDashboard($user, $period),
            'kasubag_umum'      => $this->kasubagDashboard($period),
            'petugas_gudang'    => $this->gudangDashboard(),
        };
    }

    /** Deret 6 bulan terakhir: [label bulan (locale) => nilai]. */
    private function monthlyTrend(\Closure $valueFor): array
    {
        $trend = [];
        for ($i = 5; $i >= 0; $i--) {
            $m = now()->subMonthsNoOverflow($i);
            $trend[$m->isoFormat('MMM')] = (int) $valueFor($m->copy()->startOfMonth(), $m->copy()->endOfMonth());
        }

        return $trend;
    }

    private function adminDashboard(string $period)
    {
        $items        = Item::with('category')->get();
        $lowStock     = $items->where('stock', '>', 0)->filter(fn($i) => $i->stock <= $i->minimum_stock);
        $outStock     = $items->where('stock', '<=', 0);
        $pending      = ItemRequest::where('status', 'pending')->count();
        $overReqs     = ItemRequest::where('status', 'pending')->where('is_flagged', true)->count();
        $stockInCount = StockIn::where('date', 'like', "$period%")->count();

        $recentReqs = ItemRequest::with(['department', 'requester'])
            ->latest()
            ->take(5)
            ->get();

        $deptStats = Department::with(['requests' => fn($q) => $q->where('request_date', 'like', "$period%")])->get()
            ->map(fn($dept) => [
                'dept'      => $dept,
                'total'     => $dept->requests->count(),
                'disetujui' => $dept->requests->where('status', 'disetujui')->count(),
                'ditolak'   => $dept->requests->where('status', 'ditolak')->count(),
                'selesai'   => $dept->requests->whereIn('status', ['selesai', 'selesai_sebagian'])->count(),
                'flagged'   => $dept->requests->where('is_flagged', true)->count(),
            ]);

        // Grafik: tren barang keluar 6 bulan + permintaan per bidang bulan ini.
        $outTrend  = $this->monthlyTrend(fn ($a, $b) => StockOut::whereBetween('date', [$a, $b])->sum('quantity'));
        $deptChart = $deptStats->mapWithKeys(fn ($ds) => [$ds['dept']->code => $ds['total']])->all();

        return view('dashboard.admin', compact(
            'items', 'lowStock', 'outStock', 'pending',
            'stockInCount', 'overReqs', 'recentReqs', 'deptStats', 'period',
            'outTrend', 'deptChart'
        ));
    }

    private function kabidDashboard(User $user, string $period)
    {
        $deptId = $user->department_id;
        $dept   = $user->department;

        $myReqs  = ItemRequest::with(['details.item'])
            ->where('department_id', $deptId)
            ->where('request_date', 'like', "$period%")
            ->get();

        $pending = $myReqs->where('status', 'pending')->count();
        $selesai = $myReqs->whereIn('status', ['selesai', 'selesai_sebagian'])->count();

        $totalDiterima = StockOut::where('type', 'request')
            ->whereHas('request', fn($q) => $q->where('department_id', $deptId)->where('request_date', 'like', "$period%"))
            ->sum('quantity');

        $recent = ItemRequest::with(['details.item'])
            ->where('department_id', $deptId)
            ->latest()
            ->take(4)
            ->get();

        // Logika kuota dipusatkan di DepartmentQuotaService (dipakai juga halaman Sisa Kuota Bidang).
        $quotaItems = $this->quota->itemUsage($deptId)
            ->filter(fn ($qi) => $qi['total'] > 0)
            ->values();

        return view('dashboard.kabid', compact(
            'user', 'dept', 'myReqs', 'pending', 'selesai',
            'totalDiterima', 'recent', 'quotaItems', 'period'
        ));
    }

    private function kasubagDashboard(string $period)
    {
        $pending = ItemRequest::with(['department', 'requester'])
            ->where('status', 'pending')
            ->latest()
            ->get();
        $flagged = $pending->where('is_flagged', true)->count();

        $recentApproved = ItemRequest::with(['department', 'requester'])
            ->whereIn('status', ['disetujui', 'ditolak'])
            ->where('request_date', 'like', "$period%")
            ->latest()
            ->take(5)
            ->get();

        $deptSummary = Department::all()->map(fn($dept) => [
            'dept'    => $dept,
            'pending' => ItemRequest::where('department_id', $dept->id)->where('request_date', 'like', "$period%")->where('status', 'pending')->count(),
            'total'   => ItemRequest::where('department_id', $dept->id)->where('request_date', 'like', "$period%")->count(),
            'flagged' => ItemRequest::where('department_id', $dept->id)->where('request_date', 'like', "$period%")->where('is_flagged', true)->count(),
        ]);

        // Grafik: tren permintaan masuk 6 bulan.
        $reqTrend = $this->monthlyTrend(fn ($a, $b) => ItemRequest::whereBetween('request_date', [$a, $b])->count());

        return view('dashboard.kasubag', compact('pending', 'flagged', 'recentApproved', 'deptSummary', 'period', 'reqTrend'));
    }

    private function gudangDashboard()
    {
        $items      = Item::with('category')->get();
        $low        = $items->where('stock', '>', 0)->filter(fn($i) => $i->stock <= $i->minimum_stock);
        $out        = $items->where('stock', '<=', 0);
        $totalStok  = $items->sum('stock');
        $approved   = ItemRequest::with(['department', 'details.item'])
            ->where('status', 'disetujui')
            ->latest()
            ->get();

        return view('dashboard.gudang', compact('items', 'low', 'out', 'totalStok', 'approved'));
    }
}
