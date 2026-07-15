<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSatuanRequest;
use App\Http\Requests\UpdateSatuanRequest;
use App\Models\Item;
use App\Models\Unit;

/**
 * Master Satuan — daftar satuan barang (rim, lusin, buah, …) yang dipakai
 * sebagai pilihan dropdown pada form barang (tidak lagi diketik manual).
 */
class SatuanController extends Controller
{
    public function index()
    {
        // Jumlah barang per satuan (unit disimpan sebagai nama string di items.unit).
        $usage = Item::selectRaw('unit, COUNT(*) as total')->groupBy('unit')->pluck('total', 'unit');

        $satuan = Unit::orderBy('name')->paginate(15)->through(function (Unit $u) use ($usage) {
            $u->items_using = (int) ($usage[$u->name] ?? 0);
            return $u;
        });

        return view('satuan.index', compact('satuan'));
    }

    public function store(StoreSatuanRequest $request)
    {
        Unit::create($request->validated());

        return back()->with('success', 'Satuan berhasil ditambahkan.');
    }

    public function update(UpdateSatuanRequest $request, Unit $satuan)
    {
        $satuan->update($request->validated());

        return back()->with('success', 'Satuan berhasil diperbarui.');
    }

    public function destroy(Unit $satuan)
    {
        if (Item::where('unit', $satuan->name)->exists()) {
            return back()->with('error', 'Satuan tidak dapat dihapus karena masih dipakai barang.');
        }

        $satuan->delete();

        return back()->with('success', 'Satuan berhasil dihapus.');
    }
}
