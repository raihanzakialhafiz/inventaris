<?php

namespace App\Http\Controllers;

use App\Http\Requests\SupplierRequest;
use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        $query = Supplier::withTrashed()->withCount('stockIns');

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn($q) => $q->where('name', 'like', like_contains($s))
                ->orWhere('phone', 'like', like_contains($s))
                ->orWhere('email', 'like', like_contains($s)));
        }

        $sortable = ['name', 'phone', 'email', 'created_at'];
        $sort     = in_array($request->sort, $sortable) ? $request->sort : 'created_at';
        $dir      = $request->dir === 'asc' ? 'asc' : 'desc';
        $perPage  = in_array((int)$request->per_page, [10, 25, 50, 100]) ? (int)$request->per_page : 15;

        $suppliers = $query->orderBy($sort, $dir)->paginate($perPage)->withQueryString();
        return view('supplier.index', compact('suppliers'));
    }

    public function store(SupplierRequest $request)
    {
        Supplier::create($request->validated());
        return back()->with('success', 'Supplier berhasil ditambahkan.');
    }

    public function update(SupplierRequest $request, Supplier $supplier)
    {
        $supplier->update($request->validated());
        return back()->with('success', 'Supplier berhasil diperbarui.');
    }

    public function destroy(Supplier $supplier)
    {
        $supplier->delete();
        return back()->with('success', 'Supplier berhasil dihapus.');
    }
}
