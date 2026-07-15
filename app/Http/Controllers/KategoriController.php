<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreKategoriRequest;
use App\Http\Requests\UpdateKategoriRequest;
use App\Models\Category;

class KategoriController extends Controller
{
    public function index()
    {
        $kategori = Category::withTrashed()->withCount('items')->latest()->paginate(15);
        return view('kategori.index', compact('kategori'));
    }

    public function store(StoreKategoriRequest $request)
    {
        Category::create($request->validated());
        return back()->with('success', 'Kategori berhasil ditambahkan.');
    }

    public function update(UpdateKategoriRequest $request, Category $kategori)
    {
        $kategori->update($request->validated());
        return back()->with('success', 'Kategori berhasil diperbarui.');
    }

    public function destroy(Category $kategori)
    {
        if ($kategori->items()->exists()) {
            return back()->with('error', 'Kategori tidak dapat dihapus karena masih memiliki barang.');
        }
        $kategori->delete();
        return back()->with('success', 'Kategori berhasil dihapus.');
    }
}
