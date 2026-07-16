<?php

namespace App\Http\Controllers;

use App\Exports\LaporanExport;
use App\Http\Requests\StoreBarangMasukRequest;
use App\Models\Item;
use App\Models\StockIn;
use App\Models\StockInDetail;
use App\Models\Supplier;
use App\Models\AuditLog;
use App\Support\ExportTable;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;

class BarangMasukController extends Controller
{
    public function index(Request $request)
    {
        $sortable = ['transaction_no', 'date', 'created_at'];
        $sort     = in_array($request->sort, $sortable) ? $request->sort : 'created_at';
        $dir      = $request->dir === 'asc' ? 'asc' : 'desc';
        $perPage  = in_array((int)$request->per_page, [10, 25, 50, 100]) ? (int)$request->per_page : 20;

        $stockIns  = $this->buildQuery($request)->orderBy($sort, $dir)->paginate($perPage)->withQueryString();
        $items     = Item::orderBy('name')->get();
        $suppliers = Supplier::orderBy('name')->get();

        return view('barang-masuk.index', compact('stockIns', 'items', 'suppliers'));
    }

    /** Ekspor PDF/Excel riwayat barang masuk — mengikuti filter aktif. */
    public function export(Request $request, string $format)
    {
        abort_unless(in_array($format, ['pdf', 'excel'], true), 404);

        $stockIns = $this->buildQuery($request)->latest('date')->limit(5000)->get();

        $rows = $stockIns->map(fn ($si) => [
            $si->transaction_no,
            optional($si->date)->format('d/m/Y'),
            $si->supplier->name ?? '—',
            $si->details->count() . ' jenis',
            $si->details->sum('quantity'),
            $si->createdBy->name ?? '—',
        ])->all();

        $table = ExportTable::make(
            'Riwayat Barang Masuk',
            ['No. Transaksi', 'Tanggal', 'Supplier', 'Jenis Barang', 'Total Unit', 'Petugas'],
            $rows,
            'barang-masuk-' . now()->format('Ymd'),
            withSignerSignature: $request->boolean('ttd'),
        );

        return $format === 'pdf'
            ? Pdf::loadView('laporan.pdf', $table)->setPaper('a4', 'landscape')->download($table['filename'] . '.pdf')
            : Excel::download(new LaporanExport($table), $table['filename'] . '.xlsx');
    }

    private function buildQuery(Request $request)
    {
        $query = StockIn::with(['supplier', 'details.item', 'createdBy']);

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn($q) => $q->where('transaction_no', 'like', like_contains($s))
                ->orWhereHas('supplier', fn($sq) => $sq->where('name', 'like', like_contains($s))));
        }
        if ($request->filled('date_from')) {
            $query->whereDate('date', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('date', '<=', $request->date_to);
        }

        return $query;
    }

    public function show(StockIn $barangMasuk)
    {
        $barangMasuk->load(['supplier', 'details.item', 'createdBy']);
        return view('barang-masuk.show', ['stockIn' => $barangMasuk]);
    }

    public function store(StoreBarangMasukRequest $request)
    {
        DB::transaction(function () use ($request) {
            $month = now()->format('ym');
            // lockForUpdate menyerialisasi penomoran agar tidak duplikat saat submit bersamaan.
            $last  = StockIn::where('transaction_no', 'like', "BMS-{$month}-%")->lockForUpdate()->count() + 1;
            $no    = "BMS-{$month}-" . str_pad($last, 3, '0', STR_PAD_LEFT);

            $stockIn = StockIn::create([
                'transaction_no' => $no,
                'supplier_id'    => $request->supplier_id,
                'date'           => $request->date,
                'note'           => $request->note,
                'created_by'     => auth()->id(),
            ]);

            foreach ($request->items as $line) {
                StockInDetail::create([
                    'stock_in_id' => $stockIn->id,
                    'item_id'     => $line['item_id'],
                    'quantity'    => $line['qty'],
                ]);

                Item::find($line['item_id'])->increment('stock', $line['qty']);
            }

            AuditLog::create([
                'user_id'     => auth()->id(),
                'activity'    => 'create_stock_in',
                'entity_type' => 'stock_ins',
                'entity_id'   => $stockIn->id,
                'new_values'  => ['no' => $no, 'items_count' => count($request->items)],
                'ip_address'  => request()->ip(),
            ]);
        });

        return redirect()->route('barang-masuk.index')->with('success', 'Barang masuk berhasil dicatat, stok diperbarui.');
    }
}
