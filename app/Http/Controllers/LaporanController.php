<?php

namespace App\Http\Controllers;

use App\Exports\LaporanExport;
use App\Models\Department;
use App\Support\ExportTable;
use App\Models\Item;
use App\Models\Request as ItemRequest;
use App\Models\StockIn;
use App\Models\StockOut;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class LaporanController extends Controller
{
    /** Tipe laporan valid + judulnya. */
    private const TYPES = [
        'stok'       => 'Laporan Stok Barang',
        'masuk'      => 'Laporan Barang Masuk',
        'keluar'     => 'Laporan Barang Keluar',
        'permintaan' => 'Laporan Permintaan',
        'bidang'     => 'Laporan Rekap per Bidang',
    ];

    public function index(Request $request)
    {
        [$type, $period, $deptId] = $this->params($request);

        $data = match($type) {
            'stok'       => $this->laporanStok(),
            'masuk'      => $this->laporanMasuk($period),
            'keluar'     => $this->laporanKeluar($period, $deptId),
            'permintaan' => $this->laporanPermintaan($period, $deptId),
            'bidang'     => $this->laporanBidang($period),
            default      => $this->laporanStok(),
        };

        $departments = Department::orderBy('name')->get();
        return view('laporan.index', array_merge(compact('type', 'period', 'departments', 'deptId'), $data));
    }

    /** Ekspor ke PDF (kop dinamis dari Pengaturan Sistem), menghormati filter aktif. */
    public function exportPdf(Request $request)
    {
        $table = $this->tableFor(...$this->params($request));

        return Pdf::loadView('laporan.pdf', $table)
            ->setPaper('a4', $table['type'] === 'stok' ? 'portrait' : 'landscape')
            ->download($table['filename'] . '.pdf');
    }

    /** Ekspor ke Excel (.xlsx), menghormati filter aktif. */
    public function exportExcel(Request $request)
    {
        $table = $this->tableFor(...$this->params($request));

        return Excel::download(new LaporanExport($table), $table['filename'] . '.xlsx');
    }

    /** Normalisasi parameter permintaan (tipe, periode, bidang) dengan penguncian data Kabid. */
    private function params(Request $request): array
    {
        $user   = auth()->user();
        $type   = array_key_exists($request->get('type'), self::TYPES) ? $request->get('type') : 'stok';
        // Periode dipakai sebagai prefiks LIKE — wajib format YYYY-MM agar
        // input bebas tidak bisa memperlebar cakupan laporan.
        $period = preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', (string) $request->get('period'))
            ? $request->get('period')
            : now()->format('Y-m');
        $deptId = $user->role === 'kepala_bidang' ? $user->department_id : $request->get('dept');

        return [$type, $period, $deptId, $request->boolean('ttd')];
    }

    /** Bentuk tabular (judul, header, baris) untuk ekspor — memakai query yang sama dengan tampilan. */
    private function tableFor(string $type, string $period, ?int $deptId, bool $withSigner = false): array
    {
        $dept    = $deptId ? Department::find($deptId) : null;
        $headers = [];
        $rows    = [];

        switch ($type) {
            case 'masuk':
                $headers = ['No. Transaksi', 'Tanggal', 'Supplier', 'Jenis Barang', 'Total Unit'];
                foreach ($this->laporanMasuk($period)['stockIns'] as $si) {
                    $rows[] = [$si->transaction_no, optional($si->date)->format('d/m/Y'),
                               $si->supplier->name ?? '—', $si->details->count() . ' jenis', $si->details->sum('quantity')];
                }
                break;

            case 'keluar':
                $headers = ['No. Transaksi', 'Tanggal', 'Barang', 'Bidang', 'Jumlah'];
                foreach ($this->laporanKeluar($period, $deptId)['stockOuts'] as $so) {
                    $rows[] = [$so->transaction_no, optional($so->date)->format('d/m/Y'),
                               $so->item->name ?? '—', $so->department->name ?? '—', $so->quantity];
                }
                break;

            case 'permintaan':
                $headers = ['No. Permintaan', 'Tanggal', 'Bidang', 'Pengaju', 'Status', 'Jml Item'];
                foreach ($this->laporanPermintaan($period, $deptId)['requests'] as $r) {
                    $rows[] = [$r->request_no, optional($r->request_date)->format('d/m/Y'),
                               $r->department->name ?? '—', $r->requester->name ?? '—',
                               ucfirst(str_replace('_', ' ', $r->status)), $r->details->count()];
                }
                break;

            case 'bidang':
                $headers = ['Bidang', 'Total', 'Disetujui', 'Ditolak', 'Selesai', 'Over-Request', 'Total Qty'];
                foreach ($this->laporanBidang($period)['deptStats'] as $d) {
                    $rows[] = [$d['dept']->name, $d['total'], $d['disetujui'], $d['ditolak'],
                               $d['selesai'], $d['flagged'], $d['totalQty']];
                }
                break;

            default: // stok
                $type    = 'stok';
                $headers = ['Kode', 'Nama Barang', 'Kategori', 'Satuan', 'Stok', 'Min. Stok', 'Status'];
                foreach ($this->laporanStok()['items'] as $it) {
                    $rows[] = [$it['code'], $it['name'], $it['category']['name'] ?? '—',
                               $it['unit'], $it['stock'], $it['minimum_stock'], $it['status_label']];
                }
        }

        $periodLabel = in_array($type, ['stok'], true) ? null : $period;

        // 'type' ditimpa: ExportTable memakai 'umum', sedangkan di sini tipe
        // laporan yang sebenarnya menentukan orientasi kertas PDF.
        return array_merge(
            ExportTable::make(
                self::TYPES[$type],
                $headers,
                $rows,
                'laporan-' . $type . '-' . ($periodLabel ?? now()->format('Y-m')),
                $periodLabel,
                $dept?->name,
                $withSigner,
            ),
            ['type' => $type],
        );
    }

    private function laporanStok(): array
    {
        $items = Item::with('category')->orderBy('name')->get()->map(function ($item) {
            $st = $item->stock <= 0 ? 'Habis' : ($item->stock <= $item->minimum_stock ? 'Menipis' : 'Aman');
            return array_merge($item->toArray(), ['status_label' => $st]);
        });
        return ['items' => $items];
    }

    private function laporanMasuk(string $period): array
    {
        $stockIns = StockIn::with(['supplier', 'details.item'])
            ->where('date', 'like', "$period%")
            ->latest()
            ->get();
        return ['stockIns' => $stockIns];
    }

    private function laporanKeluar(string $period, ?int $deptId): array
    {
        $query = StockOut::with(['item.category', 'department'])
            ->where('date', 'like', "$period%");
        if ($deptId) $query->where('department_id', $deptId);
        $stockOuts = $query->latest()->get();
        return ['stockOuts' => $stockOuts];
    }

    private function laporanPermintaan(string $period, ?int $deptId): array
    {
        $query = ItemRequest::with(['department', 'requester', 'details'])
            ->where('request_date', 'like', "$period%");
        if ($deptId) $query->where('department_id', $deptId);
        $requests = $query->latest()->get();
        return ['requests' => $requests];
    }

    private function laporanBidang(string $period): array
    {
        $deptStats = Department::with(['requests' => fn($q) => $q->where('request_date', 'like', "$period%")->with('details')])->get()
            ->map(function ($dept) use ($period) {
                $reqs    = $dept->requests;
                $done    = $reqs->whereIn('status', ['selesai', 'selesai_sebagian']);
                $totalQty = StockOut::where('type', 'request')
                    ->where('department_id', $dept->id)
                    ->where('date', 'like', "$period%")
                    ->sum('quantity');
                return [
                    'dept'      => $dept,
                    'total'     => $reqs->count(),
                    'disetujui' => $reqs->where('status', 'disetujui')->count(),
                    'ditolak'   => $reqs->where('status', 'ditolak')->count(),
                    'selesai'   => $done->count(),
                    'flagged'   => $reqs->where('is_flagged', true)->count(),
                    'totalQty'  => $totalQty,
                ];
            });
        return ['deptStats' => $deptStats];
    }
}
