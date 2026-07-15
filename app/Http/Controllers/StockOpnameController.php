<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStockOpnameRequest;
use App\Models\AuditLog;
use App\Models\Item;
use App\Models\StockOpname;
use Illuminate\Support\Facades\DB;

/**
 * Stock Opname — rekonsiliasi stok sistem vs hitungan fisik.
 * Petugas memasukkan jumlah fisik per barang; sistem mencatat selisih dan
 * menyesuaikan stok agar akurat. Setiap sesi tersimpan sebagai jejak audit.
 */
class StockOpnameController extends Controller
{
    public function index()
    {
        $opnames = StockOpname::with('createdBy')->latest()->paginate(15);
        $items   = Item::with('category')->orderBy('name')->get();

        return view('stock-opname.index', compact('opnames', 'items'));
    }

    public function store(StoreStockOpnameRequest $request)
    {
        DB::transaction(function () use ($request) {
            $month = now()->format('ym');
            // lockForUpdate menyerialisasi penomoran OPN antar sesi bersamaan.
            $seq = StockOpname::where('opname_no', 'like', "OPN-{$month}-%")->lockForUpdate()->count() + 1;
            $no  = "OPN-{$month}-" . str_pad($seq, 3, '0', STR_PAD_LEFT);

            $opname = StockOpname::create([
                'opname_no'  => $no,
                'date'       => today(),
                'note'       => $request->note,
                'created_by' => auth()->id(),
            ]);

            $checked = 0;
            $adjusted = 0;

            foreach ($request->input('counts', []) as $itemId => $physical) {
                // Kunci baris & baca stok sistem TERKINI (bisa berubah sejak dihitung).
                $item = Item::whereKey($itemId)->lockForUpdate()->first();
                if (! $item) {
                    continue;
                }

                $physical = (int) $physical;
                $system   = (int) $item->stock;
                $diff     = $physical - $system;

                $opname->details()->create([
                    'item_id'        => $item->id,
                    'system_stock'   => $system,
                    'physical_stock' => $physical,
                    'difference'     => $diff,
                ]);

                $checked++;
                if ($diff !== 0) {
                    $item->update(['stock' => $physical]);
                    $adjusted++;
                }
            }

            $opname->update(['items_count' => $checked, 'adjusted_count' => $adjusted]);

            AuditLog::create([
                'user_id'     => auth()->id(),
                'activity'    => 'stock_opname',
                'entity_type' => 'stock_opnames',
                'entity_id'   => $opname->id,
                'new_values'  => ['no' => $no, 'checked' => $checked, 'adjusted' => $adjusted],
                'ip_address'  => request()->ip(),
            ]);
        });

        return redirect()->route('stock-opname.index')
            ->with('success', 'Stock opname tersimpan & stok disesuaikan.');
    }

    public function show(StockOpname $stockOpname)
    {
        $stockOpname->load(['details.item', 'createdBy']);

        return view('stock-opname.show', ['opname' => $stockOpname]);
    }

    /** Cetak berita acara satu sesi opname (PDF). */
    public function export(StockOpname $stockOpname)
    {
        $stockOpname->load(['details.item', 'createdBy']);

        $rows = $stockOpname->details->map(fn ($d) => [
            $d->item->code ?? '—',
            $d->item->name ?? '—',
            $d->system_stock,
            $d->physical_stock,
            $d->difference > 0 ? '+' . $d->difference : $d->difference,
        ])->all();

        $table = \App\Support\ExportTable::make(
            "Berita Acara Stock Opname {$stockOpname->opname_no}",
            ['Kode', 'Nama Barang', 'Stok Sistem', 'Stok Fisik', 'Selisih'],
            $rows,
            'stock-opname-' . $stockOpname->opname_no,
            optional($stockOpname->date)->format('d/m/Y'),
            'Petugas: ' . ($stockOpname->createdBy->name ?? '—'),
        );

        return \Barryvdh\DomPDF\Facade\Pdf::loadView('laporan.pdf', $table)
            ->download($table['filename'] . '.pdf');
    }
}
