<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Item;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StockOpnameTest extends TestCase
{
    use RefreshDatabase;

    public function test_opname_menyesuaikan_stok_dan_mencatat_selisih(): void
    {
        $admin    = $this->makeUser('admin');
        $kategori = Category::create(['name' => 'Kertas', 'description' => '-']);
        $a = Item::create(['code' => 'OP-1', 'category_id' => $kategori->id, 'name' => 'Barang A', 'unit' => 'pcs', 'stock' => 50, 'minimum_stock' => 5]);
        $b = Item::create(['code' => 'OP-2', 'category_id' => $kategori->id, 'name' => 'Barang B', 'unit' => 'pcs', 'stock' => 30, 'minimum_stock' => 5]);

        // Fisik A = 45 (kurang 5), B = 30 (sama).
        $this->actingAs($admin)->post('/stock-opname', [
            'note'   => 'Opname uji',
            'counts' => [$a->id => 45, $b->id => 30],
        ])->assertRedirect('/stock-opname');

        $this->assertSame(45, $a->fresh()->stock);
        $this->assertSame(30, $b->fresh()->stock);

        $this->assertDatabaseHas('stock_opnames', ['items_count' => 2, 'adjusted_count' => 1]);
        $this->assertDatabaseHas('stock_opname_details', [
            'item_id' => $a->id, 'system_stock' => 50, 'physical_stock' => 45, 'difference' => -5,
        ]);
    }

    public function test_kepala_bidang_tidak_boleh_opname(): void
    {
        $kabid = $this->makeUser('kepala_bidang');

        $this->actingAs($kabid)->get('/stock-opname')->assertForbidden();
    }
}
