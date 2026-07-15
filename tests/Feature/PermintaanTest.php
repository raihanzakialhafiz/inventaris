<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Department;
use App\Models\Item;
use App\Models\Request as ItemRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PermintaanTest extends TestCase
{
    use RefreshDatabase;

    private User $kabid;
    private Item $item;

    protected function setUp(): void
    {
        parent::setUp();

        $dept        = Department::create(['code' => 'TIK', 'name' => 'Bidang TIK']);
        $this->kabid = $this->makeUser('kepala_bidang', $dept->id);

        $kategori   = Category::create(['name' => 'Kertas', 'description' => '-']);
        // minimum_stock 20 → kuota bulanan 40 → ambang over-request 60.
        $this->item = Item::create([
            'code' => 'ATK-T01', 'category_id' => $kategori->id, 'name' => 'Kertas Uji',
            'unit' => 'rim', 'stock' => 100, 'minimum_stock' => 20,
        ]);
    }

    public function test_permintaan_normal_berhasil_dibuat(): void
    {
        $this->actingAs($this->kabid)
            ->post('/permintaan', ['items' => [['item_id' => $this->item->id, 'qty' => 5]]])
            ->assertRedirect(route('permintaan.index'));

        $this->assertDatabaseCount('requests', 1);
        $this->assertFalse(ItemRequest::first()->is_flagged);
    }

    public function test_over_request_tanpa_justifikasi_ditolak_server(): void
    {
        $this->actingAs($this->kabid)
            ->post('/permintaan', ['items' => [['item_id' => $this->item->id, 'qty' => 100]]])
            ->assertSessionHasErrors('justification');

        $this->assertDatabaseCount('requests', 0);
    }

    public function test_over_request_dengan_justifikasi_ditandai_flag(): void
    {
        $this->actingAs($this->kabid)->post('/permintaan', [
            'items'         => [['item_id' => $this->item->id, 'qty' => 100]],
            'justification' => 'Kebutuhan rapat besar akhir tahun',
        ]);

        $this->assertDatabaseCount('requests', 1);
        $this->assertTrue(ItemRequest::first()->is_flagged);
    }

    public function test_qty_melebihi_batas_atas_ditolak(): void
    {
        $this->actingAs($this->kabid)
            ->post('/permintaan', ['items' => [['item_id' => $this->item->id, 'qty' => 100001]]])
            ->assertSessionHasErrors('items.0.qty');

        $this->assertDatabaseCount('requests', 0);
    }

    public function test_petugas_gudang_tidak_boleh_mengajukan(): void
    {
        $gudang = $this->makeUser('petugas_gudang');

        $this->actingAs($gudang)
            ->post('/permintaan', ['items' => [['item_id' => $this->item->id, 'qty' => 5]]])
            ->assertForbidden();
    }
}
