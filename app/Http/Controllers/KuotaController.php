<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreKuotaRequest;
use App\Http\Requests\UpdateKuotaRequest;
use App\Models\Category;
use App\Models\Department;
use App\Models\Item;
use App\Models\RequestQuota;

class KuotaController extends Controller
{
    public function index()
    {
        $kuota       = RequestQuota::with(['department', 'item', 'category'])->latest()->paginate(15);
        $departments = Department::orderBy('name')->get();
        $items       = Item::orderBy('name')->get();
        $categories  = Category::orderBy('name')->get();
        return view('kuota.index', compact('kuota', 'departments', 'items', 'categories'));
    }

    public function store(StoreKuotaRequest $request)
    {
        RequestQuota::create($request->validated());
        return back()->with('success', 'Kuota berhasil ditambahkan.');
    }

    public function update(UpdateKuotaRequest $request, RequestQuota $kuotum)
    {
        $kuotum->update($request->validated());
        return back()->with('success', 'Kuota berhasil diperbarui.');
    }

    public function destroy(RequestQuota $kuotum)
    {
        $kuotum->delete();
        return back()->with('success', 'Kuota berhasil dihapus.');
    }
}
