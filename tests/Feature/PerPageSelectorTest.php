<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Department;
use App\Models\Item;
use App\Models\StockOut;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Selektor "Tampilkan N data" harus menampilkan nilai per-halaman yang benar
 * dipakai, walau nilai itu (mis. default 15) tidak termasuk opsi bawaan
 * 10/25/50/100 — kalau tidak, dropdown menampilkan 10 padahal tabel memakai 15.
 */
class PerPageSelectorTest extends TestCase
{
    use RefreshDatabase;

    private function buat14Riwayat(): User
    {
        $gudang   = $this->makeUser('petugas_gudang');
        $dept     = Department::create(['code' => 'TIK', 'name' => 'Bidang TIK']);
        $kategori = Category::create(['name' => 'ATK', 'description' => '-']);
        $item     = Item::create(['code' => 'ATK-001', 'category_id' => $kategori->id, 'name' => 'Kertas', 'unit' => 'rim', 'stock' => 500, 'minimum_stock' => 5]);

        for ($i = 1; $i <= 14; $i++) {
            StockOut::create([
                'transaction_no' => 'BKL-' . str_pad($i, 3, '0', STR_PAD_LEFT),
                'item_id' => $item->id, 'department_id' => $dept->id,
                'quantity' => 1, 'type' => 'request', 'date' => today(), 'created_by' => $gudang->id,
            ]);
        }

        return $gudang;
    }

    public function test_default_15_ditampilkan_sebagai_opsi_terpilih_bukan_10(): void
    {
        $gudang = $this->buat14Riwayat();

        $html = $this->actingAs($gudang)->get('/distribusi')->assertOk()->getContent();

        // Opsi 15 harus ADA dan terpilih (dulu: tak ada, dropdown jatuh ke 10).
        $this->assertMatchesRegularExpression('/<option value="15"[^>]*selected[^>]*>15<\/option>/', $html);
        // 14 baris < 15 → benar satu halaman, tidak ada tautan riwayat halaman 2.
        $this->assertStringNotContainsString('page=2', $html);
    }

    public function test_pilih_10_memunculkan_halaman_2(): void
    {
        $gudang = $this->buat14Riwayat();

        $html = $this->actingAs($gudang)->get('/distribusi?per_page=10')->assertOk()->getContent();

        $this->assertMatchesRegularExpression('/<option value="10"[^>]*selected[^>]*>10<\/option>/', $html);
        // 14 baris, 10 per halaman → ada tautan ke halaman 2.
        $this->assertStringContainsString('page=2', $html);
    }
}
