<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{
    public function showLoginForm()
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ], [
            'email.required'    => 'Email wajib diisi.',
            'email.email'       => 'Format email tidak valid.',
            'password.required' => 'Password wajib diisi.',
        ]);

        $user = User::where('email', $request->email)->first();

        // Akun tidak ditemukan atau tidak aktif
        if (!$user || !$user->is_active) {
            throw ValidationException::withMessages([
                'email' => 'Email atau password salah.',
            ]);
        }

        // Cek lockout
        if ($user->isLocked()) {
            $menit = now()->diffInMinutes($user->locked_until) + 1;
            throw ValidationException::withMessages([
                'email' => "Akun terkunci. Coba lagi dalam {$menit} menit.",
            ]);
        }

        // Verifikasi password
        if (!Hash::check($request->password, $user->password)) {
            $user->increment('failed_login_count');

            if ($user->failed_login_count >= 5) {
                $user->update(['locked_until' => now()->addMinutes(15)]);
                throw ValidationException::withMessages([
                    'email' => 'Terlalu banyak percobaan. Akun dikunci 15 menit.',
                ]);
            }

            // Pesan sengaja sama dengan akun tak terdaftar — sisa percobaan tidak
            // disebut agar respons tidak bisa dipakai menebak email terdaftar.
            throw ValidationException::withMessages([
                'email' => 'Email atau password salah.',
            ]);
        }

        // Login berhasil — reset counter
        $user->update(['failed_login_count' => 0, 'locked_until' => null]);

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        AuditLog::create([
            'user_id'     => $user->id,
            'activity'    => 'login',
            'entity_type' => 'users',
            'entity_id'   => $user->id,
            'ip_address'  => $request->ip(),
        ]);

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request)
    {
        $userId = Auth::id();

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($userId) {
            AuditLog::create([
                'user_id'     => $userId,
                'activity'    => $request->boolean('timeout') ? 'session_timeout' : 'logout',
                'entity_type' => 'users',
                'entity_id'   => $userId,
                'ip_address'  => $request->ip(),
            ]);
        }

        // Logout yang dipicu idle-timeout dari sisi klien → tampilkan pesan di halaman login.
        if ($request->boolean('timeout')) {
            return redirect()->route('login', ['timeout' => 1]);
        }

        return redirect()->route('login');
    }
}
