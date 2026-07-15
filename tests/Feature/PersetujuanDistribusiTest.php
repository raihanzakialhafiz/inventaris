<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Department;
use App\Models\Item;
use App\Models\Request as ItemRequest;
use App\Models\RequestDetail;
use App\Models\StockOut;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Integritas alur persetujuan & distribusi:
 * - persetujuan atomik (tidak ada simpan sebagian) dan menolak semua-nol;
 * - distribusi mencatat hasil semua baris (termasuk qty 0 + alasannya).
 */
class PersetujuanDistribusiTest extends TestCase
{
    use RefreshDatabase;

    private User $kabid;
    private User $kasubag;
    private User $gudang;
    private Item $item;

    protected function setUp(): void
    {
        parent::setUp();

        $dept          = Department::create(['code' => 'TIK', 'name' => 'Bidang TIK']);
        $this->kabid   = $this->makeUser('kepala_bidang', $dept->id);
        $this->kasubag = $this->makeUser('kasubag_umum');
        $this->gudang  = $this->makeUser('petugas_gudang');

        $kategori   = Category::create(['name' => 'Kertas', 'description' => '-']);
        $this->item = Item::create([
            'code' => 'ATK-T01', 'category_id' => $kategori->id, 'name' => 'Kertas Uji',
            'unit' => 'rim', 'stock' => 100, 'minimum_stock' => 20,
        ]);
    }

    /** @param array<int, array{Item, int, ?int}> $lines [item, diminta, disetujui] */
    private function buatPermintaan(array $lines, string $status = 'pending'): ItemRequest
    {
        $req = ItemRequest::create([
            'request_no'    => 'PRM-TST-' . str_pad(ItemRequest::count() + 1, 3, '0', STR_PAD_LEFT),
            'user_id'       => $this->kabid->id,
            'department_id' => $this->kabid->department_id,
            'request_date'  => today(),
            'status'        => $status,
        ]);

        foreach ($lines as [$item, $requested, $approved]) {
            RequestDetail::create([
                'request_id'         => $req->id,
                'item_id'            => $item->id,
                'quantity_requested' => $requested,
                'quantity_approved'  => $approved,
            ]);
        }

        return $req;
    }

    public function test_persetujuan_semua_nol_ditolak(): void
    {
        $req    = $this->buatPermintaan([[$this->item, 10, null]]);
        $detail = $req->details()->first();

        $this->actingAs($this->kasubag)
            ->post("/permintaan/{$req->id}/approve", [
                'approved_quantities' => [$detail->id => 0],
            ])
            ->assertSessionHas('error');

        $this->assertSame('pending', $req->fresh()->status);
    }

    public function test_persetujuan_qty_melebihi_tidak_menyimpan_sebagian(): void
    {
        $item2 = Item::create([
            'code' => 'ATK-T02', 'category_id' => $this->item->category_id, 'name' => 'Pulpen Uji',
            'unit' => 'buah', 'stock' => 50, 'minimum_stock' => 5,
        ]);
        $req = $this->buatPermintaan([[$this->item, 10, null], [$item2, 5, null]]);
        [$d1, $d2] = $req->details()->orderBy('id')->get();

        $this->actingAs($this->kasubag)
            ->post("/permintaan/{$req->id}/approve", [
                'approved_quantities' => [$d1->id => 3, $d2->id => 99],
            ])
            ->assertSessionHas('error');

        // Baris pertama TIDAK boleh ikut tersimpan saat baris kedua gagal validasi.
        $this->assertNull($d1->fresh()->quantity_approved);
        $this->assertSame('pending', $req->fresh()->status);
    }

    public function test_persetujuan_normal_berhasil(): void
    {
        $req    = $this->buatPermintaan([[$this->item, 10, null]]);
        $detail = $req->details()->first();

        $this->actingAs($this->kasubag)
            ->post("/permintaan/{$req->id}/approve", [
                'approved_quantities' => [$detail->id => 8],
            ])
            ->assertSessionHas('success');

        $this->assertSame('disetujui', $req->fresh()->status);
        $this->assertSame(8, (int) $detail->fresh()->quantity_approved);
    }

    public function test_permintaan_yang_sudah_diproses_tidak_bisa_disetujui_lagi(): void
    {
        $req    = $this->buatPermintaan([[$this->item, 10, 10]], 'disetujui');
        $detail = $req->details()->first();

        $this->actingAs($this->kasubag)
            ->post("/permintaan/{$req->id}/approve", [
                'approved_quantities' => [$detail->id => 10],
            ])
            ->assertSessionHas('error');

        $this->assertSame('disetujui', $req->fresh()->status);
    }

    public function test_distribusi_qty_nol_menyimpan_alasan(): void
    {
        $req    = $this->buatPermintaan([[$this->item, 5, 5]], 'disetujui');
        $detail = $req->details()->first();

        $this->actingAs($this->gudang)
            ->post("/distribusi/{$req->id}", [
                'distributions' => [
                    $detail->id => ['qty' => 0, 'reduction_reason' => 'Stok fisik rusak terkena air'],
                ],
            ])
            ->assertSessionHas('success');

        $detail = $detail->fresh();
        $this->assertSame(0, (int) $detail->quantity_distributed);
        $this->assertSame('Stok fisik rusak terkena air', $detail->reduction_reason);
        $this->assertSame('selesai_sebagian', $req->fresh()->status);
        $this->assertSame(100, (int) $this->item->fresh()->stock);
        $this->assertDatabaseCount('stock_outs', 0);
    }

    public function test_distribusi_penuh_mengurangi_stok(): void
    {
        $req    = $this->buatPermintaan([[$this->item, 5, 5]], 'disetujui');
        $detail = $req->details()->first();

        $this->actingAs($this->gudang)
            ->post("/distribusi/{$req->id}", [
                'distributions' => [$detail->id => ['qty' => 5]],
            ])
            ->assertSessionHas('success');

        $this->assertSame('selesai', $req->fresh()->status);
        $this->assertSame(95, (int) $this->item->fresh()->stock);
        $this->assertSame(5, (int) $detail->fresh()->quantity_distributed);
        $this->assertSame(1, StockOut::where('request_id', $req->id)->count());
    }

    public function test_permintaan_yang_sudah_selesai_tidak_bisa_didistribusikan_lagi(): void
    {
        $req    = $this->buatPermintaan([[$this->item, 5, 5]], 'selesai');
        $detail = $req->details()->first();

        $this->actingAs($this->gudang)
            ->post("/distribusi/{$req->id}", [
                'distributions' => [$detail->id => ['qty' => 5]],
            ])
            ->assertSessionHas('error');

        $this->assertSame(100, (int) $this->item->fresh()->stock);
        $this->assertDatabaseCount('stock_outs', 0);
    }
}
