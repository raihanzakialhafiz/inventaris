<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Department;
use App\Models\Item;
use App\Models\Request as ItemRequest;
use App\Models\RequestQuota;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuotaTest extends TestCase
{
    use RefreshDatabase;

    private User $kabid;
    private Item $item;

    protected function setUp(): void
    {
        parent::setUp();

        $dept        = Department::create(['code' => 'TIK', 'name' => 'Bidang TIK']);
        $this->kabid = $this->makeUser('kepala_bidang', $dept->id);
        $kategori    = Category::create(['name' => 'Kertas', 'description' => '-']);
        $this->item  = Item::create([
            'code' => 'ATK-Q1', 'category_id' => $kategori->id, 'name' => 'Kertas Kuota',
            'unit' => 'rim', 'stock' => 500, 'minimum_stock' => 10,
        ]);
    }

    private function quota(string $policy): void
    {
        RequestQuota::create([
            'department_id' => $this->kabid->department_id,
            'item_id'       => $this->item->id,
            'period_type'   => 'monthly',
            'quota_quantity' => 10,      // limit = 10 × 150% = 15
            'threshold_percent' => 150,
            'cooldown_days' => 0,
            'policy'        => $policy,
            'effective_from' => today(),
            'is_active'     => true,
        ]);
    }

    public function test_kebijakan_block_menolak_permintaan_melebihi_ambang(): void
    {
        $this->quota('block');

        $this->actingAs($this->kabid)
            ->from('/permintaan')
            ->post('/permintaan', ['items' => [['item_id' => $this->item->id, 'qty' => 20]]])
            ->assertRedirect('/permintaan')
            ->assertSessionHas('error');

        $this->assertDatabaseCount('requests', 0);
    }

    public function test_kebijakan_block_meloloskan_dalam_ambang(): void
    {
        $this->quota('block');

        $this->actingAs($this->kabid)
            ->post('/permintaan', ['items' => [['item_id' => $this->item->id, 'qty' => 10]]]);

        $this->assertDatabaseCount('requests', 1);
    }

    public function test_kebijakan_warn_wajib_justifikasi_lalu_ditandai_flag(): void
    {
        $this->quota('warn');

        // Tanpa justifikasi → ditolak validasi.
        $this->actingAs($this->kabid)
            ->post('/permintaan', ['items' => [['item_id' => $this->item->id, 'qty' => 20]]])
            ->assertSessionHasErrors('justification');
        $this->assertDatabaseCount('requests', 0);

        // Dengan justifikasi → tersimpan & is_flagged.
        $this->actingAs($this->kabid)->post('/permintaan', [
            'items'         => [['item_id' => $this->item->id, 'qty' => 20]],
            'justification' => 'Kebutuhan besar untuk kegiatan khusus',
        ]);
        $this->assertDatabaseCount('requests', 1);
        $this->assertTrue(ItemRequest::first()->is_flagged);
    }

    public function test_kuota_global_semua_bidang_berlaku(): void
    {
        // department_id null = berlaku untuk SEMUA bidang.
        RequestQuota::create([
            'department_id' => null,
            'item_id'       => $this->item->id,
            'period_type'   => 'monthly',
            'quota_quantity' => 10, 'threshold_percent' => 150, 'cooldown_days' => 0,
            'policy'        => 'block', 'effective_from' => today(), 'is_active' => true,
        ]);

        $this->actingAs($this->kabid)
            ->from('/permintaan')
            ->post('/permintaan', ['items' => [['item_id' => $this->item->id, 'qty' => 20]]])
            ->assertSessionHas('error');

        $this->assertDatabaseCount('requests', 0);
    }

    public function test_tanpa_kuota_pakai_heuristik(): void
    {
        // minimum_stock 10 → monthlyQuota 20 → limit 30. qty 25 lolos tanpa flag.
        $this->actingAs($this->kabid)->post('/permintaan', [
            'items' => [['item_id' => $this->item->id, 'qty' => 25]],
        ]);

        $this->assertDatabaseCount('requests', 1);
        $this->assertFalse(ItemRequest::first()->is_flagged);
    }

    public function test_aturan_duplikat_cakupan_sama_ditolak(): void
    {
        $this->quota('warn'); // aturan aktif: bidang TIK + barang ini

        $this->actingAs($this->makeUser('admin'))
            ->from('/kuota')
            ->post('/kuota', [
                'department_id'     => $this->kabid->department_id,
                'item_id'           => $this->item->id,
                'period_type'       => 'monthly',
                'quota_quantity'    => 5,
                'threshold_percent' => 150,
                'cooldown_days'     => 0,
                'policy'            => 'block',
                'effective_from'    => today()->toDateString(),
            ])
            ->assertSessionHasErrors('department_id');

        $this->assertDatabaseCount('request_quotas', 1);
    }

    public function test_aturan_cakupan_berbeda_tetap_boleh(): void
    {
        $this->quota('warn'); // aturan barang

        // Aturan default bidang (tanpa barang/kategori) — cakupan berbeda.
        $this->actingAs($this->makeUser('admin'))->post('/kuota', [
            'department_id'     => $this->kabid->department_id,
            'period_type'       => 'monthly',
            'quota_quantity'    => 50,
            'threshold_percent' => 150,
            'cooldown_days'     => 0,
            'policy'            => 'warn',
            'effective_from'    => today()->toDateString(),
        ])->assertSessionDoesntHaveErrors();

        $this->assertDatabaseCount('request_quotas', 2);
    }

    public function test_aktifkan_ulang_aturan_yang_menabrak_ditolak(): void
    {
        $this->quota('warn'); // aturan A aktif

        // Aturan B: cakupan sama, nonaktif.
        $b = RequestQuota::create([
            'department_id' => $this->kabid->department_id,
            'item_id'       => $this->item->id,
            'period_type'   => 'monthly',
            'quota_quantity' => 8, 'threshold_percent' => 150, 'cooldown_days' => 0,
            'policy'        => 'warn', 'effective_from' => today(), 'is_active' => false,
        ]);

        $this->actingAs($this->makeUser('admin'))
            ->from('/kuota')
            ->put("/kuota/{$b->id}", [
                'quota_quantity'    => 8,
                'threshold_percent' => 150,
                'cooldown_days'     => 0,
                'policy'            => 'warn',
                'is_active'         => 1,
            ])
            ->assertSessionHasErrors('is_active');

        $this->assertFalse($b->fresh()->is_active);
    }
}
