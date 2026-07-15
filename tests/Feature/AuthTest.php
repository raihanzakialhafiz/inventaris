<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_halaman_login_tampil(): void
    {
        $this->get('/login')->assertOk();
    }

    public function test_tamu_diarahkan_ke_login(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_login_berhasil_dengan_kredensial_valid(): void
    {
        $user = $this->makeUser('admin');

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_login_ditolak_dengan_password_salah(): void
    {
        $user = $this->makeUser('admin');

        $this->from('/login')
            ->post('/login', ['email' => $user->email, 'password' => 'salah-total1'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_akun_nonaktif_tidak_bisa_login(): void
    {
        $user = $this->makeUser('admin');
        $user->update(['is_active' => false]);

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_pesan_gagal_login_tidak_membocorkan_akun_terdaftar(): void
    {
        $user = $this->makeUser('admin');

        // Email terdaftar & tidak terdaftar harus mendapat pesan yang sama —
        // respons tidak boleh bisa dipakai menebak akun mana yang ada.
        $this->from('/login')
            ->post('/login', ['email' => $user->email, 'password' => 'salah-total1'])
            ->assertSessionHasErrors(['email' => 'Email atau password salah.']);

        $this->from('/login')
            ->post('/login', ['email' => 'tidak-terdaftar@contoh.test', 'password' => 'salah-total1'])
            ->assertSessionHasErrors(['email' => 'Email atau password salah.']);
    }

    public function test_header_keamanan_terpasang(): void
    {
        $this->get('/login')
            ->assertHeader('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeader('X-Content-Type-Options', 'nosniff')
            ->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
    }
}
