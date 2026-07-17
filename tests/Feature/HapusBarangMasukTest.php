<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Item;
use App\Models\StockIn;
use App\Models\StockInDetail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Hapus barang masuk (koreksi salah input) — dua tingkat: per-baris barang dan
 * transaksi utuh. Keduanya mengembalikan stok, dan DITOLAK bila stok barang
 * sudah terpakai (menghindari stok minus).
 */
class HapusBarangMasukTest extends TestCase
{
    use RefreshDatabase;

    private User $gudang;
    private Item $kertas;
    private Item $pulpen;

    protected function setUp(): void
    {
        parent::setUp();
        $this->gudang = $this->makeUser('petugas_gudang');
        $kategori     = Category::create(['name' => 'ATK', 'description' => '-']);
        $this->kertas = Item::create(['code' => 'ATK-001', 'category_id' => $kategori->id, 'name' => 'Kertas', 'unit' => 'rim', 'stock' => 50, 'minimum_stock' => 5]);
        $this->pulpen = Item::create(['code' => 'ATK-002', 'category_id' => $kategori->id, 'name' => 'Pulpen', 'unit' => 'pcs', 'stock' => 30, 'minimum_stock' => 5]);
    }

    /** Transaksi berisi 2 baris: kertas 20 rim + pulpen 10 pcs (stok sudah termasuk). */
    private function transaksi(): StockIn
    {
        $si = StockIn::create([
            'transaction_no' => 'BMS-9999-001', 'date' => today(), 'created_by' => $this->gudang->id,
        ]);
        StockInDetail::create(['stock_in_id' => $si->id, 'item_id' => $this->kertas->id, 'quantity' => 20]);
        StockInDetail::create(['stock_in_id' => $si->id, 'item_id' => $this->pulpen->id, 'quantity' => 10]);

        return $si;
    }

    public function test_hapus_satu_baris_mengembalikan_stok_barang_itu_saja(): void
    {
        $si = $this->transaksi();
        $detailKertas = $si->details()->where('item_id', $this->kertas->id)->first();

        $this->actingAs($this->gudang)
            ->delete("/barang-masuk/{$si->id}/detail/{$detailKertas->id}")
            ->assertSessionHas('success');

        $this->assertSame(30, $this->kertas->fresh()->stock);       // 50 - 20
        $this->assertSame(30, $this->pulpen->fresh()->stock);       // tak tersentuh
        $this->assertDatabaseMissing('stock_in_details', ['id' => $detailKertas->id]);
        $this->assertDatabaseHas('stock_ins', ['id' => $si->id]);   // masih ada 1 baris
    }

    public function test_hapus_baris_terakhir_ikut_menghapus_transaksinya(): void
    {
        $si = $this->transaksi();

        foreach ($si->details as $d) {
            $this->actingAs($this->gudang)->delete("/barang-masuk/{$si->id}/detail/{$d->id}");
        }

        $this->assertDatabaseMissing('stock_ins', ['id' => $si->id]);
        $this->assertSame(30, $this->kertas->fresh()->stock);
        $this->assertSame(20, $this->pulpen->fresh()->stock);
    }

    public function test_hapus_transaksi_utuh_mengembalikan_semua_stok(): void
    {
        $si = $this->transaksi();

        $this->actingAs($this->gudang)
            ->delete("/barang-masuk/{$si->id}")
            ->assertRedirect(route('barang-masuk.index'));

        $this->assertDatabaseMissing('stock_ins', ['id' => $si->id]);
        $this->assertDatabaseMissing('stock_in_details', ['stock_in_id' => $si->id]);
        $this->assertSame(30, $this->kertas->fresh()->stock);
        $this->assertSame(20, $this->pulpen->fresh()->stock);
    }

    public function test_ditolak_bila_stok_sudah_terpakai(): void
    {
        $si = $this->transaksi();
        // Stok kertas tinggal 12 (< 20 yang disumbang transaksi ini) — seolah
        // sebagian sudah didistribusikan.
        $this->kertas->update(['stock' => 12]);

        $this->actingAs($this->gudang)
            ->delete("/barang-masuk/{$si->id}")
            ->assertSessionHas('error');

        // Tidak ada yang berubah — termasuk pulpen yang sebenarnya cukup.
        $this->assertSame(12, $this->kertas->fresh()->stock);
        $this->assertSame(30, $this->pulpen->fresh()->stock);
        $this->assertDatabaseHas('stock_ins', ['id' => $si->id]);
        $this->assertSame(2, $si->details()->count());
    }

    public function test_baris_transaksi_lain_tidak_bisa_dihapus_lewat_transaksi_ini(): void
    {
        $si   = $this->transaksi();
        $lain = StockIn::create(['transaction_no' => 'BMS-9999-002', 'date' => today(), 'created_by' => $this->gudang->id]);
        $detailLain = StockInDetail::create(['stock_in_id' => $lain->id, 'item_id' => $this->kertas->id, 'quantity' => 5]);

        $this->actingAs($this->gudang)
            ->delete("/barang-masuk/{$si->id}/detail/{$detailLain->id}")
            ->assertNotFound();
    }

    public function test_kepala_bidang_tidak_boleh_menghapus(): void
    {
        $si = $this->transaksi();

        $this->actingAs($this->makeUser('kepala_bidang'))
            ->delete("/barang-masuk/{$si->id}")
            ->assertForbidden();
    }
}
