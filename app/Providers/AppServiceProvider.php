<?php

namespace App\Providers;

use App\Http\View\Composers\SidebarComposer;
use App\Models\Setting;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        // Produksi wajib HTTPS: semua URL yang dihasilkan memakai skema https
        // (redirect 80→443 tetap tugas web server — lihat DEPLOYMENT.md).
        if ($this->app->isProduction()) {
            URL::forceScheme('https');
        }

        Paginator::defaultView('vendor.pagination.default');
        View::composer('layouts.app', SidebarComposer::class);

        // Rate limit login per kombinasi email+IP (bukan IP saja) agar satu
        // kantor di belakang NAT tidak saling mengunci, tapi brute force
        // terhadap satu akun tetap tertahan. Melengkapi lockout per-akun.
        RateLimiter::for('login', function (Request $request) {
            $key = strtolower((string) $request->input('email')).'|'.$request->ip();

            return Limit::perMinute(5)->by($key);
        });

        $this->applyMailSettings();
    }

    /**
     * Terapkan akun email dari Pengaturan (DB) menimpa .env, bila diisi.
     * Transport (host/port/enkripsi/metode) tetap dari .env. Akun pengirim
     * (email) sekaligus jadi username SMTP. Password tersimpan terenkripsi.
     * Bila sebuah nilai kosong → jatuh ke .env (fallback aman).
     */
    private function applyMailSettings(): void
    {
        // Aman saat instalasi awal / sebelum migrasi.
        if (! Schema::hasTable('settings')) {
            return;
        }

        $from = Setting::get('mail_from_address');
        if (filled($from)) {
            config([
                'mail.mailers.smtp.username' => $from,   // Gmail: username = alamat pengirim
                'mail.from.address'          => $from,
            ]);
        }

        if (filled($name = Setting::get('mail_from_name'))) {
            config(['mail.from.name' => $name]);
        }

        if (filled($password = Setting::get('mail_password'))) {
            try {
                config(['mail.mailers.smtp.password' => Crypt::decryptString($password)]);
            } catch (\Throwable $e) {
                // Nilai rusak → biarkan pakai .env
            }
        }
    }
}
