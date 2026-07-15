<?php

namespace Tests\Feature;

use App\Models\Department;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_kepala_bidang_diblokir_dari_halaman_terlarang(): void
    {
        $dept  = Department::create(['code' => 'TIK', 'name' => 'Bidang TIK']);
        $kabid = $this->makeUser('kepala_bidang', $dept->id);

        foreach (['/barang', '/pengguna', '/sampah', '/laporan', '/pengaturan', '/barang-masuk', '/distribusi'] as $path) {
            $this->actingAs($kabid)->get($path)->assertForbidden();
        }
    }

    public function test_kepala_bidang_bisa_akses_sisa_kuota_bidang(): void
    {
        $dept  = Department::create(['code' => 'TIK', 'name' => 'Bidang TIK']);
        $kabid = $this->makeUser('kepala_bidang', $dept->id);

        $this->actingAs($kabid)->get('/kuota-bidang')->assertOk();
    }

    public function test_kasubag_bisa_lihat_barang_tapi_bukan_master_data(): void
    {
        $kasubag = $this->makeUser('kasubag_umum');

        $this->actingAs($kasubag)->get('/barang')->assertOk();
        $this->actingAs($kasubag)->get('/pengguna')->assertForbidden();
        $this->actingAs($kasubag)->get('/sampah')->assertForbidden();
    }

    public function test_petugas_gudang_akses_gudang_bukan_persetujuan(): void
    {
        $gudang = $this->makeUser('petugas_gudang');

        $this->actingAs($gudang)->get('/barang-masuk')->assertOk();
        $this->actingAs($gudang)->get('/pengguna')->assertForbidden();
        $this->actingAs($gudang)->get('/pengaturan')->assertForbidden();
    }

    public function test_admin_akses_halaman_sistem(): void
    {
        $admin = $this->makeUser('admin');

        $this->actingAs($admin)->get('/pengguna')->assertOk();
        $this->actingAs($admin)->get('/sampah')->assertOk();
        $this->actingAs($admin)->get('/pengaturan')->assertOk();
    }
}
