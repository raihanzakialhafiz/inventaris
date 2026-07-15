<?php

namespace App\Http\Controllers;

use App\Services\DepartmentQuotaService;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

/**
 * Sisa Kuota Bidang — ringkasan pemakaian & sisa kuota permintaan ATK
 * untuk bidang milik Kepala Bidang yang sedang login (periode berjalan).
 */
class KuotaBidangController extends Controller
{
    public function index(Request $request, DepartmentQuotaService $quota)
    {
        $user = auth()->user();

        // itemUsage() menghasilkan Collection terkomputasi (bukan query),
        // jadi dipaginasi manual seperti pola Kotak Sampah.
        $all     = $quota->itemUsage($user->department_id);
        $perPage = 15;
        $page    = LengthAwarePaginator::resolveCurrentPage();
        $quotaItems = new LengthAwarePaginator(
            $all->forPage($page, $perPage)->values(),
            $all->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('kuota-bidang.index', [
            'quotaItems' => $quotaItems,
            'dept'       => $user->department,
            'period'     => now()->format('Y-m'),
        ]);
    }
}
