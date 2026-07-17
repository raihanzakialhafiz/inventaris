<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Department;
use App\Models\Item;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tombol hapus master data (kategori/bidang/satuan) SELALU tampil, juga saat
 * data sedang dipakai — penjaga sisi server yang menolak, bukan UI yang
 * menyembunyikan. Sebelumnya tombol hilang total saat dipakai, membingungkan.
 */
class TombolHapusMasterTest extends TestCase
{
    use RefreshDatabase;

    private function barangDenganKategoriDanSatuan(Category $kat, string $unit): void
    {
        Item::create(['code' => 'ATK-999', 'category_id' => $kat->id, 'name' => 'Barang Uji', 'unit' => $unit, 'stock' => 1, 'minimum_stock' => 1]);
    }

    public function test_tombol_hapus_kategori_tampil_walau_berisi_barang(): void
    {
        $admin = $this->makeUser('admin');
        $kat   = Category::create(['name' => 'Terpakai', 'description' => '-']);
        $this->barangDenganKategoriDanSatuan($kat, 'pcs');

        $this->actingAs($admin)->get('/kategori')->assertOk()
            ->assertSee(route('kategori.destroy', $kat), false);

        // Dan server tetap menolak.
        $this->actingAs($admin)->delete(route('kategori.destroy', $kat))->assertSessionHas('error');
        $this->assertDatabaseHas('categories', ['id' => $kat->id]);
    }

    public function test_tombol_hapus_bidang_tampil_walau_ada_pegawai(): void
    {
        $admin = $this->makeUser('admin');
        $dept  = Department::create(['code' => 'TIK', 'name' => 'Bidang TIK']);
        $this->makeUser('kepala_bidang', $dept->id);

        $this->actingAs($admin)->get('/bidang')->assertOk()
            ->assertSee(route('bidang.destroy', $dept), false);

        $this->actingAs($admin)->delete(route('bidang.destroy', $dept))->assertSessionHas('error');
        $this->assertDatabaseHas('departments', ['id' => $dept->id]);
    }

    public function test_tombol_hapus_satuan_tampil_walau_dipakai(): void
    {
        $admin = $this->makeUser('admin');
        $unit  = Unit::create(['name' => 'rim', 'description' => '-']);
        $kat   = Category::create(['name' => 'K', 'description' => '-']);
        $this->barangDenganKategoriDanSatuan($kat, 'rim');

        $this->actingAs($admin)->get('/satuan')->assertOk()
            ->assertSee(route('satuan.destroy', $unit), false);

        $this->actingAs($admin)->delete(route('satuan.destroy', $unit))->assertSessionHas('error');
        $this->assertDatabaseHas('units', ['id' => $unit->id]);
    }

    public function test_hapus_tetap_berhasil_saat_tidak_dipakai(): void
    {
        $admin = $this->makeUser('admin');
        $kat   = Category::create(['name' => 'Kosong', 'description' => '-']);
        $unit  = Unit::create(['name' => 'lusin', 'description' => '-']);
        $dept  = Department::create(['code' => 'KOS', 'name' => 'Bidang Kosong']);

        $this->actingAs($admin)->delete(route('kategori.destroy', $kat))->assertSessionHas('success');
        $this->actingAs($admin)->delete(route('satuan.destroy', $unit))->assertSessionHas('success');
        $this->actingAs($admin)->delete(route('bidang.destroy', $dept))->assertSessionHas('success');
    }
}
