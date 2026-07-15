<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBarangRequest;
use App\Http\Requests\UpdateBarangRequest;
use App\Models\Category;
use App\Models\Item;
use App\Models\Unit;
use Illuminate\Http\Request;

class BarangController extends Controller
{
    public function index(Request $request)
    {
        $query = Item::with('category');

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn($q) => $q->where('name', 'like', like_contains($s))->orWhere('code', 'like', like_contains($s)));
        }
        if ($request->filled('category')) {
            $query->where('category_id', $request->category);
        }
        if ($request->filled('status')) {
            match($request->status) {
                'ok'  => $query->whereColumn('stock', '>', 'minimum_stock'),
                'low' => $query->where('stock', '>', 0)->whereColumn('stock', '<=', 'minimum_stock'),
                'out' => $query->where('stock', '<=', 0),
                default => null,
            };
        }

        $sortable = ['code', 'name', 'stock', 'minimum_stock', 'created_at'];
        $sort     = in_array($request->sort, $sortable) ? $request->sort : 'created_at';
        $dir      = $request->dir === 'asc' ? 'asc' : 'desc';
        $perPage  = in_array((int)$request->per_page, [10, 25, 50, 100]) ? (int)$request->per_page : 20;

        $items      = $query->orderBy($sort, $dir)->paginate($perPage)->withQueryString();
        $categories = Category::orderBy('name')->get();
        // Satuan sbg pilihan dropdown (value=label=nama, karena items.unit menyimpan nama).
        $units      = Unit::orderBy('name')->pluck('name', 'name');

        return view('barang.index', compact('items', 'categories', 'units'));
    }

    public function store(StoreBarangRequest $request)
    {
        Item::create($request->validated());
        return redirect()->route('barang.index')->with('success', 'Barang berhasil ditambahkan.');
    }

    public function update(UpdateBarangRequest $request, Item $barang)
    {
        // Rules update tidak memuat `stock` — stok hanya berubah lewat transaksi.
        $barang->update($request->validated());
        return redirect()->route('barang.index')->with('success', 'Barang berhasil diperbarui.');
    }

    public function destroy(Item $barang)
    {
        // Barang yang masih dimuat permintaan aktif tidak boleh dihapus —
        // alur persetujuan/distribusi masih membutuhkannya.
        $dipakaiPermintaanAktif = $barang->requestDetails()
            ->whereHas('request', fn ($q) => $q->whereIn('status', ['pending', 'disetujui']))
            ->exists();

        if ($dipakaiPermintaanAktif) {
            return back()->with('error', 'Barang tidak dapat dihapus karena masih dimuat permintaan yang belum selesai diproses.');
        }

        $barang->delete();
        return back()->with('success', 'Barang berhasil dihapus.');
    }
}
