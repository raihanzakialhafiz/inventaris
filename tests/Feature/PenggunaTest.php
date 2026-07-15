<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Manajemen pengguna: admin tidak boleh mengunci dirinya sendiri
 * (menghapus, menonaktifkan, atau menurunkan peran akun sendiri).
 */
class PenggunaTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = $this->makeUser('admin');
    }

    private function payload(User $user, array $override = []): array
    {
        return array_merge([
            'name'      => $user->name,
            'email'     => $user->email,
            'role'      => $user->role,
            'is_active' => 1,
        ], $override);
    }

    public function test_admin_tidak_bisa_menonaktifkan_akun_sendiri(): void
    {
        $this->actingAs($this->admin)
            ->put("/pengguna/{$this->admin->id}", $this->payload($this->admin, ['is_active' => 0]))
            ->assertSessionHas('error');

        $this->assertTrue($this->admin->fresh()->is_active);
    }

    public function test_admin_tidak_bisa_menurunkan_peran_sendiri(): void
    {
        $this->actingAs($this->admin)
            ->put("/pengguna/{$this->admin->id}", $this->payload($this->admin, ['role' => 'pimpinan']))
            ->assertSessionHas('error');

        $this->assertSame('admin', $this->admin->fresh()->role);
    }

    public function test_admin_masih_bisa_mengubah_profil_sendiri(): void
    {
        $this->actingAs($this->admin)
            ->put("/pengguna/{$this->admin->id}", $this->payload($this->admin, ['name' => 'Nama Baru']))
            ->assertSessionHas('success');

        $this->assertSame('Nama Baru', $this->admin->fresh()->name);
    }

    public function test_admin_bisa_menonaktifkan_pengguna_lain(): void
    {
        $lain = $this->makeUser('pimpinan');

        $this->actingAs($this->admin)
            ->put("/pengguna/{$lain->id}", $this->payload($lain, ['is_active' => 0]))
            ->assertSessionHas('success');

        $this->assertFalse($lain->fresh()->is_active);
    }
}
