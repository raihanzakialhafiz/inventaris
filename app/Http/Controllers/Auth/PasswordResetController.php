<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

/**
 * Alur lupa & reset kata sandi (bawaan Laravel Password broker).
 * Tautan reset dikirim via email (driver `log` di dev → cek storage/logs).
 * Hanya akun aktif yang menerima tautan; kebijakan sandi seragam (huruf+angka).
 */
class PasswordResetController extends Controller
{
    public function showLinkRequestForm()
    {
        return view('auth.forgot-password');
    }

    public function sendResetLink(Request $request)
    {
        $request->validate(['email' => 'required|email'], [
            'email.required' => 'Email wajib diisi.',
            'email.email'    => 'Format email tidak valid.',
        ]);

        // Hanya akun aktif — pesan sukses tetap generik (anti enumerasi email).
        $status = Password::sendResetLink([
            'email'     => $request->email,
            'is_active' => true,
        ]);

        return back()->with('status', __($status));
    }

    public function showResetForm(Request $request, string $token)
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => $request->query('email', ''),
        ]);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'token'    => 'required',
            'email'    => 'required|email',
            'password' => ['required', 'confirmed', 'min:8', 'regex:/[a-zA-Z]/', 'regex:/[0-9]/'],
        ], [
            'password.confirmed' => 'Konfirmasi password tidak cocok.',
            'password.min'       => 'Password minimal 8 karakter.',
            'password.regex'     => 'Password harus kombinasi huruf dan angka.',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function ($user, $password) use ($request) {
                $user->forceFill([
                    'password'       => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                AuditLog::create([
                    'user_id'     => $user->id,
                    'activity'    => 'reset_password',
                    'entity_type' => 'users',
                    'entity_id'   => $user->id,
                    'ip_address'  => $request->ip(),
                ]);

                event(new PasswordReset($user));
            }
        );

        return $status === Password::PasswordReset
            ? redirect()->route('login')->with('status', 'Password berhasil diatur ulang. Silakan masuk.')
            : back()->withErrors(['email' => __($status)]);
    }
}
