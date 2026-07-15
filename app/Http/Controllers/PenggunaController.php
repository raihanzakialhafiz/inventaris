<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePenggunaRequest;
use App\Http\Requests\UpdatePenggunaRequest;
use App\Models\AuditLog;
use App\Models\User;
use App\Models\Department;
use App\Models\StockIn;
use App\Models\StockOut;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class PenggunaController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with('department');

        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(fn($q) => $q->where('name', 'like', like_contains($s))->orWhere('email', 'like', like_contains($s)));
        }
        if ($request->filled('role')) {
            $query->where('role', $request->role);
        }

        $sortable = ['name', 'email', 'role', 'created_at'];
        $sort     = in_array($request->sort, $sortable) ? $request->sort : 'name';
        $dir      = $request->dir === 'asc' ? 'asc' : 'desc';
        $perPage  = in_array((int)$request->per_page, [10, 25, 50, 100]) ? (int)$request->per_page : 20;

        $users       = $query->orderBy($sort, $dir)->paginate($perPage)->withQueryString();
        $departments = Department::orderBy('name')->get();
        return view('pengguna.index', compact('users', 'departments'));
    }

    public function store(StorePenggunaRequest $request)
    {
        User::create([
            'name'          => $request->name,
            'email'         => $request->email,
            'password'      => Hash::make($request->password),
            'role'          => $request->role,
            'department_id' => $request->department_id,
            'is_active'     => $request->boolean('is_active', true),
        ]);

        return back()->with('success', 'Pengguna berhasil ditambahkan.');
    }

    public function update(UpdatePenggunaRequest $request, User $pengguna)
    {
        $data = $request->safe()->only('name', 'email', 'role', 'department_id');
        $data['is_active'] = $request->boolean('is_active', true);

        // Jangan sampai admin mengunci dirinya sendiri dari sistem.
        if ($pengguna->id === auth()->id()
            && (($data['role'] ?? $pengguna->role) !== $pengguna->role || ! $data['is_active'])) {
            return back()->with('error', 'Tidak dapat mengubah peran atau menonaktifkan akun sendiri.');
        }

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);

            // Password dikecualikan dari audit otomatis (hash tidak boleh bocor) —
            // catat manual bahwa admin mengganti password akun ini.
            AuditLog::create([
                'user_id'     => auth()->id(),
                'activity'    => 'change_password',
                'entity_type' => 'users',
                'entity_id'   => $pengguna->id,
                'ip_address'  => $request->ip(),
            ]);
        }

        $pengguna->update($data);
        return back()->with('success', 'Pengguna berhasil diperbarui.');
    }

    public function destroy(User $pengguna)
    {
        if ($pengguna->id === auth()->id()) {
            return back()->with('error', 'Tidak dapat menghapus akun sendiri.');
        }

        // FK restrictOnDelete: pengguna dengan riwayat transaksi tidak bisa
        // dihapus dari database — arahkan ke nonaktifkan akun.
        $punyaRiwayat = $pengguna->requests()->exists()
            || StockIn::where('created_by', $pengguna->id)->exists()
            || StockOut::where('created_by', $pengguna->id)->exists();

        if ($punyaRiwayat) {
            return back()->with('error', 'Pengguna memiliki riwayat transaksi sehingga tidak dapat dihapus. Nonaktifkan akun ini sebagai gantinya.');
        }

        $pengguna->delete();
        return back()->with('success', 'Pengguna berhasil dihapus.');
    }
}
