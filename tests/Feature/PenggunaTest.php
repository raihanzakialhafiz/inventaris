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

    /**
     * Regresi: NIP & jabatan sempat divalidasi tapi dibuang controller (tidak
     * masuk daftar field yang disimpan). Uji lewat HTTP, bukan model langsung —
     * lewat model, bug seperti ini tidak terdeteksi.
     */
    public function test_nip_dan_jabatan_tersimpan_lewat_form_tambah(): void
    {
        $this->actingAs($this->admin)->post('/pengguna', [
            'name'      => 'Pegawai Baru',
            'email'     => 'baru@siatk.test',
            'nip'       => '199001012015031001',
            'jabatan'   => 'Pengelola Barang',
            'password'  => 'rahasia123',
            'role'      => 'petugas_gudang',
            'is_active' => 1,
        ])->assertSessionHas('success');

        $baru = User::where('email', 'baru@siatk.test')->first();
        $this->assertSame('199001012015031001', $baru->nip);
        $this->assertSame('Pengelola Barang', $baru->jabatan);
    }

    /**
     * Regresi: validasi gagal sempat menutup modal dan mengosongkan seluruh
     * isian. Modal harus terbuka kembali dengan ketikan pengguna masih utuh.
     */
    public function test_validasi_gagal_membuka_ulang_modal_dengan_isian_utuh(): void
    {
        $this->actingAs($this->admin)
            ->from('/pengguna')
            ->followingRedirects()
            ->post('/pengguna', [
                'name'      => 'Pegawai Baru',
                'email'     => $this->admin->email,   // duplikat → gagal
                'password'  => 'rahasia123',
                'role'      => 'petugas_gudang',
                'is_active' => 1,
            ])
            ->assertOk()
            ->assertSee('showModal: true', false)   // modal terbuka lagi
            ->assertSee('Pegawai Baru');            // ketikan tidak hilang
    }

    public function test_bidang_wajib_untuk_kepala_bidang(): void
    {
        $this->actingAs($this->admin)->post('/pengguna', [
            'name'      => 'Kabid Tanpa Bidang',
            'email'     => 'kabid@siatk.test',
            'password'  => 'rahasia123',
            'role'      => 'kepala_bidang',
            'is_active' => 1,
        ])->assertSessionHasErrors('department_id');

        $this->assertDatabaseMissing('users', ['email' => 'kabid@siatk.test']);
    }

    public function test_nip_dan_jabatan_tersimpan_lewat_form_edit(): void
    {
        $lain = $this->makeUser('pimpinan');

        $this->actingAs($this->admin)
            ->put("/pengguna/{$lain->id}", $this->payload($lain, [
                'nip' => '198203152006041002', 'jabatan' => 'Kepala Dinas',
            ]))->assertSessionHas('success');

        $lain->refresh();
        $this->assertSame('198203152006041002', $lain->nip);
        $this->assertSame('Kepala Dinas', $lain->jabatan);
    }
}
