<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class ProfilController extends Controller
{
    public function edit()
    {
        return view('profil.index', ['user' => auth()->user()]);
    }

    /** Perbarui data profil (nama & email). */
    public function update(Request $request)
    {
        $user = auth()->user();

        $data = $request->validate([
            'name'  => 'required|string|max:150',
            'email' => ['required', 'email', 'max:100', Rule::unique('users', 'email')->ignore($user->id)],
        ]);

        // Perubahan nama/email dicatat otomatis (trait Auditable, dengan nilai lama→baru).
        $user->update($data);

        return back()->with('success', 'Profil berhasil diperbarui.');
    }

    /** Ganti password (verifikasi password lama, PRD: min 8, huruf+angka). */
    public function updatePassword(Request $request)
    {
        $user = auth()->user();

        $request->validate([
            'current_password' => 'required',
            'password'         => ['required', 'confirmed', 'min:8', 'regex:/[a-zA-Z]/', 'regex:/[0-9]/'],
        ], [
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
            'password.min'       => 'Password minimal 8 karakter.',
            'password.regex'     => 'Password harus kombinasi huruf dan angka.',
        ]);

        if (! Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'Password saat ini salah.'])->with('pw_error', true);
        }

        $user->update(['password' => Hash::make($request->password)]);

        AuditLog::create([
            'user_id'     => $user->id,
            'activity'    => 'change_password',
            'entity_type' => 'users',
            'entity_id'   => $user->id,
            'ip_address'  => $request->ip(),
        ]);

        return back()->with('success', 'Password berhasil diubah.');
    }
}
