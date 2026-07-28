<?php

namespace App\Http\Controllers;

use App\Http\Requests\BidangRequest;
use App\Models\Department;
use App\Models\User;

class BidangController extends Controller
{
    public function index()
    {
        $bidang   = Department::with('headUser')->withCount('users')->latest()->paginate(15);
        $kabiList = User::where('role', 'kepala_bidang')->orderBy('name')->get();
        return view('bidang.index', compact('bidang', 'kabiList'));
    }

    public function store(BidangRequest $request)
    {
        Department::create($request->validated());
        return back()->with('success', 'Bidang berhasil ditambahkan.');
    }

    public function update(BidangRequest $request, Department $bidang)
    {
        $bidang->update($request->validated());
        return back()->with('success', 'Bidang berhasil diperbarui.');
    }

    public function destroy(Department $bidang)
    {
        if ($bidang->users()->exists() || $bidang->requests()->exists()) {
            return back()->with('error', 'Bidang tidak dapat dihapus karena masih memiliki pegawai atau permintaan aktif.');
        }
        $bidang->delete();
        return back()->with('success', 'Bidang berhasil dihapus.');
    }
}
