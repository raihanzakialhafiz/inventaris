<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_halaman_lupa_password_tampil(): void
    {
        $this->get('/lupa-password')->assertOk();
    }

    public function test_tautan_reset_dikirim_untuk_akun_aktif(): void
    {
        Notification::fake();
        $user = $this->makeUser('admin');

        $this->post('/lupa-password', ['email' => $user->email])->assertSessionHas('status');

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_akun_nonaktif_tidak_menerima_tautan(): void
    {
        Notification::fake();
        $user = $this->makeUser('admin');
        $user->update(['is_active' => false]);

        $this->post('/lupa-password', ['email' => $user->email]);

        Notification::assertNothingSent();
    }

    public function test_reset_mengubah_password_dengan_kebijakan_kuat(): void
    {
        $user  = $this->makeUser('admin');
        $token = Password::broker()->createToken($user);

        // Password lemah (tanpa angka) ditolak.
        $this->from("/reset-password/{$token}")->post('/reset-password', [
            'token' => $token, 'email' => $user->email,
            'password' => 'hurufsaja', 'password_confirmation' => 'hurufsaja',
        ])->assertSessionHasErrors('password');

        // Password kuat diterima → diarahkan ke login.
        $this->post('/reset-password', [
            'token' => $token, 'email' => $user->email,
            'password' => 'RahasiaKuat9', 'password_confirmation' => 'RahasiaKuat9',
        ])->assertRedirect(route('login'));

        $this->assertTrue(\Illuminate\Support\Facades\Hash::check('RahasiaKuat9', $user->fresh()->password));
    }
}
