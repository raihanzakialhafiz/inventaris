<?php

namespace App\Http\Controllers;

use App\Mail\ActionNotificationMail;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;

/**
 * Pengaturan Email (Admin): konfigurasi SMTP pengirim + jadwal pengiriman
 * ulang email stok menipis. Password SMTP disimpan terenkripsi.
 * Konfigurasi diterapkan runtime oleh AppServiceProvider::applyMailSettings().
 */
class PengaturanEmailController extends Controller
{
    /**
     * Kunci teks biasa (password ditangani terpisah karena dienkripsi).
     * Host/port/enkripsi/metode SENGAJA dari .env — tidak diatur di UI.
     */
    private const TEXT_KEYS = [
        'mail_from_address', 'mail_from_name',
        'stock_alert_interval_days', 'stock_alert_hour',
    ];

    public function index()
    {
        $settings    = Setting::pluck('value', 'key');
        $hasPassword = filled($settings['mail_password'] ?? null);

        // Email baru bisa terkirim bila akun DAN password sama-sama terisi;
        // salah satu kosong = jatuh ke .env, yang belum tentu diisi.
        $configured = $hasPassword && filled($settings['mail_from_address'] ?? config('mail.from.address'));

        // Ditulis penjadwal tiap kali email stok menipis terkirim.
        $lastSent = filled($settings['stock_alert_last_sent'] ?? null)
            ? Carbon::parse($settings['stock_alert_last_sent'])
            : null;

        return view('pengaturan-email.index', compact('settings', 'hasPassword', 'configured', 'lastSent'));
    }

    public function update(Request $request)
    {
        $request->validate([
            'mail_password'             => 'nullable|string|max:255',
            'mail_from_address'         => 'nullable|email|max:150',
            'mail_from_name'            => 'nullable|string|max:100',
            'stock_alert_interval_days' => 'required|integer|min:1|max:30',
            'stock_alert_hour'          => 'required|integer|min:0|max:23',
        ]);

        foreach (self::TEXT_KEYS as $key) {
            Setting::updateOrCreate(['key' => $key], ['value' => $request->input($key), 'type' => 'text']);
        }

        // Password hanya ditimpa bila diisi (kosong = pertahankan yang lama).
        if (filled($request->mail_password)) {
            Setting::updateOrCreate(
                ['key' => 'mail_password'],
                ['value' => Crypt::encryptString($request->mail_password), 'type' => 'secret']
            );
        }

        return back()->with('success', 'Pengaturan email berhasil disimpan.');
    }

    public function test(Request $request)
    {
        $request->validate(['test_email' => 'required|email']);

        try {
            Mail::to($request->test_email)->send(new ActionNotificationMail(
                'Administrator',
                'Email Uji Coba',
                'Ini adalah email uji dari sistem. Jika Anda menerimanya, konfigurasi email sudah benar.',
                config('app.url'),
                'Buka Sistem',
            ));

            return back()->with('success', "Email uji terkirim ke {$request->test_email}. Silakan cek kotak masuk.");
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal mengirim email uji: ' . $e->getMessage());
        }
    }
}
