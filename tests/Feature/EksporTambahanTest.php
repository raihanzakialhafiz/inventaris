<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Item;
use App\Models\StockOpname;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tahap 6: ekspor Audit Log / Barang Masuk / Stock Opname
 * dan indikator kekuatan password terpasang.
 */
class EksporTambahanTest extends TestCase
{
    use RefreshDatabase;

    public function test_ekspor_audit_log_excel_berhasil(): void
    {
        $admin = $this->makeUser('admin');

        $this->actingAs($admin)->get('/audit-log/export/excel')
            ->assertOk()
            ->assertDownload('audit-log-' . now()->format('Ymd') . '.xlsx');
    }

    public function test_ekspor_audit_log_ditolak_untuk_non_admin(): void
    {
        $this->actingAs($this->makeUser('petugas_gudang'))
            ->get('/audit-log/export/excel')
            ->assertForbidden();
    }

    public function test_ekspor_barang_masuk_excel_berhasil(): void
    {
        $this->actingAs($this->makeUser('petugas_gudang'))
            ->get('/barang-masuk/export/excel')
            ->assertOk()
            ->assertDownload('barang-masuk-' . now()->format('Ymd') . '.xlsx');
    }

    public function test_cetak_pdf_stock_opname_berhasil(): void
    {
        $gudang   = $this->makeUser('petugas_gudang');
        $kategori = Category::create(['name' => 'Kertas', 'description' => '-']);
        $item     = Item::create([
            'code' => 'ATK-O1', 'category_id' => $kategori->id, 'name' => 'Kertas Opname',
            'unit' => 'rim', 'stock' => 10, 'minimum_stock' => 2,
        ]);

        $opname = StockOpname::create([
            'opname_no' => 'OPN-TST-001', 'date' => today(), 'created_by' => $gudang->id,
        ]);
        $opname->details()->create([
            'item_id' => $item->id, 'system_stock' => 10, 'physical_stock' => 8, 'difference' => -2,
        ]);

        $this->actingAs($gudang)->get("/stock-opname/{$opname->id}/export")
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_indikator_kekuatan_password_terpasang_di_profil(): void
    {
        $this->actingAs($this->makeUser('admin'))->get('/profil')
            ->assertOk()
            ->assertSee('data-pw-meter', false)
            ->assertSee('pw-meter', false);
    }
}
