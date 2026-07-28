<?php

namespace App\Http\Controllers;

use App\Http\Requests\KategoriRequest;
use App\Models\Category;

class KategoriController extends Controller
{
    public function index()
    {
        $kategori = Category::withTrashed()->withCount('items')->latest()->paginate(15);
        return view('kategori.index', compact('kategori'));
    }

    public function store(KategoriRequest $request)
    {
        Category::create($request->validated());
        return back()->with('success', 'Kategori berhasil ditambahkan.');
    }

    public function update(KategoriRequest $request, Category $kategori)
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
