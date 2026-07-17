<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Item;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * UX modal tambah/edit barang: duplikat kode & nama ditolak dengan pesan
 * jelas, error tampil inline di modal (bukan toast), modal terbuka kembali
 * dengan isian pengguna, dan kode barang disarankan otomatis.
 */
class BarangModalTest extends TestCase
{
    use RefreshDatabase;

    private Category $kategori;

    protected function setUp(): void
    {
        parent::setUp();
        $this->kategori = Category::create(['name' => 'Alat Tulis', 'description' => '-']);
    }

    private function barang(string $code, string $name): Item
    {
        return Item::create([
            'code' => $code, 'category_id' => $this->kategori->id, 'name' => $name,
            'unit' => 'pcs', 'stock' => 10, 'minimum_stock' => 2,
        ]);
    }

    private function payload(array $override = []): array
    {
        return array_merge([
            'code' => 'ATK-100', 'category_id' => $this->kategori->id, 'name' => 'Pulpen Biru',
            'unit' => 'pcs', 'stock' => 5, 'minimum_stock' => 1,
        ], $override);
    }

    public function test_kode_duplikat_ditolak_dengan_pesan_jelas(): void
    {
        $this->barang('ATK-001', 'Kertas A4');

        $this->actingAs($this->makeUser('admin'))
            ->post('/barang', $this->payload(['code' => 'ATK-001']))
            ->assertSessionHasErrors(['code' => 'Kode ini sudah dipakai barang lain.']);
    }

    public function test_nama_duplikat_ditolak_dengan_pesan_jelas(): void
    {
        $this->barang('ATK-001', 'Kertas A4');

        $this->actingAs($this->makeUser('admin'))
            ->post('/barang', $this->payload(['name' => 'Kertas A4']))
            ->assertSessionHasErrors(['name' => 'Nama barang ini sudah terdaftar.']);
    }

    public function test_nama_duplikat_saat_edit_ditolak_tapi_nama_sendiri_boleh(): void
    {
        $this->barang('ATK-001', 'Kertas A4');
        $sendiri = $this->barang('ATK-002', 'Stapler');
        $admin   = $this->makeUser('admin');

        // Menyimpan tanpa mengubah nama sendiri tidak boleh dianggap duplikat.
        $this->actingAs($admin)
            ->put("/barang/{$sendiri->id}", $this->payload(['code' => 'ATK-002', 'name' => 'Stapler']))
            ->assertSessionHasNoErrors();

        // Mengganti nama menjadi milik barang lain: ditolak.
        $this->actingAs($admin)
            ->put("/barang/{$sendiri->id}", $this->payload(['code' => 'ATK-002', 'name' => 'Kertas A4']))
            ->assertSessionHasErrors('name');
    }

    public function test_gagal_validasi_membuka_ulang_modal_dengan_isian_dan_error_inline_bukan_toast(): void
    {
        $this->barang('ATK-001', 'Kertas A4');
        $admin = $this->makeUser('admin');

        $this->actingAs($admin)
            ->from('/barang')
            ->post('/barang', $this->payload(['code' => 'ATK-001', 'name' => 'Spidol Baru']))
            ->assertRedirect('/barang');

        // Ikuti redirect pada siklus session yang sama.
        $html = $this->actingAs($admin)->get('/barang')->assertOk()->getContent();

        $this->assertStringContainsString('showModal: true', $html, 'Modal tidak terbuka ulang.');
        $this->assertStringContainsString('Spidol Baru', $html);                          // isian pengguna kembali
        $this->assertStringContainsString('Kode ini sudah dipakai barang lain.', $html);  // error inline
        // Error TIDAK boleh ikut jadi toast.
        $this->assertStringNotContainsString('"error":["Kode ini sudah dipakai barang lain."]', $html);
    }

    public function test_kode_otomatis_disarankan_dari_nomor_terbesar(): void
    {
        $this->barang('ATK-007', 'Kertas A4');
        $this->barang('ATK-012', 'Stapler');

        $this->actingAs($this->makeUser('admin'))
            ->get('/barang')
            ->assertOk()
            ->assertSee('ATK-013');
    }
}
