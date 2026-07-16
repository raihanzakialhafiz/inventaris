<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

/**
 * Batas waktu sesi (idle timeout).
 *
 * Mengeluarkan pengguna secara otomatis bila tidak ada aktivitas selama
 * `session_timeout` menit (dapat diatur di Pengaturan Sistem, default 30).
 * Sisi server bersifat otoritatif — timer di sisi klien hanya membantu UX.
 */
class SessionTimeout
{
    /**
     * Rute yang dipanggil otomatis oleh halaman di latar belakang. Kehadirannya
     * bukan tanda pengguna masih aktif, jadi tidak menyegarkan timer idle —
     * tetap tunduk pada batas waktu, hanya tidak memperpanjangnya.
     */
    private const BACKGROUND_ROUTES = ['notifikasi.count'];

    public function handle(Request $request, Closure $next): Response
    {
        if (! Auth::check()) {
            return $next($request);
        }

        $timeout = (int) setting('session_timeout', 30);
        // 0/negatif = nonaktifkan timeout.
        if ($timeout < 1) {
            return $next($request);
        }

        $maxIdle  = $timeout * 60;
        $lastSeen = $request->session()->get('last_activity_at');

        if ($lastSeen !== null && (time() - (int) $lastSeen) > $maxIdle) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            if ($request->expectsJson()) {
                return response()->json(['message' => 'Sesi berakhir.'], 401);
            }

            return redirect()->route('login', ['timeout' => 1]);
        }

        if (! $request->routeIs(...self::BACKGROUND_ROUTES)) {
            $request->session()->put('last_activity_at', time());
        }

        return $next($request);
    }
}
