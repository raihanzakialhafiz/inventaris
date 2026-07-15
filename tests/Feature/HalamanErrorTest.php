<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * UX Tahap 3: halaman error kustom berbahasa Indonesia
 * dan modal peringatan idle terpasang di layout aplikasi.
 */
class HalamanErrorTest extends TestCase
{
    use RefreshDatabase;

    public function test_404_menampilkan_halaman_kustom(): void
    {
        $this->actingAs($this->makeUser('admin'))
            ->get('/halaman-yang-tidak-ada')
            ->assertNotFound()
            ->assertSee('Halaman Tidak Ditemukan');
    }

    public function test_403_menampilkan_halaman_kustom(): void
    {
        $this->actingAs($this->makeUser('kepala_bidang'))
            ->get('/pengguna')
            ->assertForbidden()
            ->assertSee('Akses Ditolak');
    }

    public function test_419_dan_500_bisa_dirender(): void
    {
        $this->view('errors.419')->assertSee('Sesi Telah Berakhir');
        $this->view('errors.500')->assertSee('Terjadi Kesalahan Server');
    }

    public function test_modal_peringatan_idle_terpasang_di_layout(): void
    {
        $this->actingAs($this->makeUser('admin'))
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('id="idle-warning"', false)
            ->assertSee('Tetap Masuk');
    }
}
