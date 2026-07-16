<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Batas waktu sesi (idle timeout) — lihat App\Http\Middleware\SessionTimeout.
 *
 * Catatan: middleware memakai time() bawaan PHP, yang tidak terpengaruh
 * travel()/Carbon::setTestNow. Karena itu waktu aktivitas terakhir diatur
 * langsung lewat session, bukan dengan memajukan jam.
 */
class SessionTimeoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_sesi_kedaluwarsa_bila_benar_benar_menganggur(): void
    {
        $user = $this->makeUser('admin');
        $this->actingAs($user)->get('/dashboard')->assertOk();

        // Aktivitas terakhir 31 menit lalu, batas bawaan 30 menit.
        $this->withSession(['last_activity_at' => time() - 31 * 60])
            ->get('/dashboard')
            ->assertRedirect(route('login', ['timeout' => 1]));
    }

    public function test_ping_dari_aktivitas_pengguna_memperpanjang_sesi(): void
    {
        $user = $this->makeUser('admin');
        $this->actingAs($user)->get('/dashboard')->assertOk();

        // Pengguna menganggur 29 menit, lalu menggerakkan mouse → app-shell.js
        // mengirim ping. Ini aktivitas nyata, jadi sesi memang harus diperpanjang.
        $this->withSession(['last_activity_at' => time() - 29 * 60])
            ->get('/sesi/ping')
            ->assertNoContent();

        $this->assertEqualsWithDelta(
            time(),
            (int) session('last_activity_at'),
            5,
            'Ping aktivitas pengguna harus menyegarkan timer idle, jika tidak pengguna aktif ikut dikeluarkan.',
        );
    }

    public function test_polling_badge_notifikasi_tidak_memperpanjang_sesi(): void
    {
        $user = $this->makeUser('admin');
        $this->actingAs($user)->get('/dashboard')->assertOk();

        // Pengguna menganggur 29 menit — tinggal semenit lagi sesinya berakhir.
        $hampirHabis = time() - 29 * 60;

        // Polling badge dari app-shell.js berjalan sendiri tiap 60 detik,
        // tanpa aktivitas pengguna sama sekali.
        $this->withSession(['last_activity_at' => $hampirHabis])
            ->get('/notifikasi/hitung')
            ->assertOk();

        // Polling otomatis bukan aktivitas pengguna, jadi tidak boleh
        // menyegarkan timer idle — kalau ikut menyegarkan, batas waktu sesi
        // tidak akan pernah tercapai selama ada satu tab terbuka.
        $this->assertEqualsWithDelta(
            $hampirHabis,
            (int) session('last_activity_at'),
            5,
            'Polling /notifikasi/hitung menyegarkan last_activity_at — idle timeout sisi server tidak pernah tercapai.',
        );
    }
}
