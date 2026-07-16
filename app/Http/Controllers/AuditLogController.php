<?php

namespace App\Http\Controllers;

use App\Exports\LaporanExport;
use App\Models\AuditLog;
use App\Models\User;
use App\Support\ExportTable;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $perPage = in_array((int)$request->per_page, [25, 50, 100]) ? (int)$request->per_page : 50;
        $logs    = $this->buildQuery($request)->paginate($perPage)->withQueryString();
        $users   = User::orderBy('name')->get();
        $entities = AuditLog::select('entity_type')->distinct()->whereNotNull('entity_type')->pluck('entity_type');

        return view('audit-log.index', compact('logs', 'users', 'entities'));
    }

    /** Ekspor PDF/Excel — memakai filter aktif yang sama dengan tampilan. */
    public function export(Request $request, string $format)
    {
        abort_unless(in_array($format, ['pdf', 'excel'], true), 404);

        // Batas atas agar ekspor riwayat panjang tidak menghabiskan memori.
        $logs = $this->buildQuery($request)->limit(5000)->get();

        $rows = $logs->map(fn ($log) => [
            optional($log->created_at)->format('d/m/Y H:i'),
            $log->user->name ?? 'Sistem',
            $log->activity,
            $log->entity_type ?? '—',
            $log->entity_id ?? '—',
            $log->ip_address ?? '—',
        ])->all();

        $table = ExportTable::make(
            'Riwayat Aktivitas (Audit Log)',
            ['Waktu', 'Pengguna', 'Aktivitas', 'Entitas', 'ID', 'Alamat IP'],
            $rows,
            'audit-log-' . now()->format('Ymd'),
            withSignerSignature: $request->boolean('ttd'),
        );

        return $format === 'pdf'
            ? Pdf::loadView('laporan.pdf', $table)->setPaper('a4', 'landscape')->download($table['filename'] . '.pdf')
            : Excel::download(new LaporanExport($table), $table['filename'] . '.xlsx');
    }

    private function buildQuery(Request $request)
    {
        $query = AuditLog::with('user')->orderByDesc('created_at');

        if ($q = $request->get('q')) {
            $query->where(function ($sub) use ($q) {
                $sub->where('activity', 'like', like_contains($q))
                    ->orWhere('entity_type', 'like', like_contains($q))
                    ->orWhere('ip_address', 'like', like_contains($q));
            });
        }
        if ($userId = $request->get('user_id')) {
            $query->where('user_id', $userId);
        }
        if ($entity = $request->get('entity_type')) {
            $query->where('entity_type', $entity);
        }
        if ($date = $request->get('date')) {
            $query->whereDate('created_at', $date);
        }

        return $query;
    }
}
