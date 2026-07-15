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
 * Aturan penghapusan data:
 * - barang dalam permintaan aktif & pengguna dengan riwayat tidak bisa dihapus;
 * - riwayat tetap tampil walau master di-soft-delete (withTrashed);
 * - hapus permanen yang melanggar FK ditangani dengan pesan ramah (bukan 500);
 * - hanya kepala bidang yang dapat mengajukan permintaan.
 */
class HapusDataTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $kabid;
    private Item $item;

    protected function setUp(): void
    {
        parent::setUp();

        $dept        = Department::create(['code' => 'TIK', 'name' => 'Bidang TIK']);
        $this->admin = $this->makeUser('admin');
        $this->kabid = $this->makeUser('kepala_bidang', $dept->id);

        $kategori   = Category::create(['name' => 'Kertas', 'description' => '-']);
        $this->item = Item::create([
            'code' => 'ATK-T01', 'category_id' => $kategori->id, 'name' => 'Kertas Uji',
            'unit' => 'rim', 'stock' => 100, 'minimum_stock' => 20,
        ]);
    }

    private function buatPermintaan(string $status): ItemRequest
    {
        $req = ItemRequest::create([
            'request_no'    => 'PRM-TST-' . str_pad(ItemRequest::count() + 1, 3, '0', STR_PAD_LEFT),
            'user_id'       => $this->kabid->id,
            'department_id' => $this->kabid->department_id,
            'request_date'  => today(),
            'status'        => $status,
        ]);

        RequestDetail::create([
            'request_id'         => $req->id,
            'item_id'            => $this->item->id,
            'quantity_requested' => 5,
            'quantity_approved'  => $status === 'pending' ? null : 5,
        ]);

        return $req;
    }

    public function test_barang_dalam_permintaan_aktif_tidak_bisa_dihapus(): void
    {
        $this->buatPermintaan('pending');

        $this->actingAs($this->admin)
            ->delete("/barang/{$this->item->id}")
            ->assertSessionHas('error');

        $this->assertNull($this->item->fresh()->deleted_at);
    }

    public function test_riwayat_tetap_tampil_setelah_barang_dihapus(): void
    {
        $req = $this->buatPermintaan('selesai');
        StockOut::create([
            'transaction_no' => 'BKL-TST-001',
            'item_id'        => $this->item->id,
            'quantity'       => 5,
            'department_id'  => $this->kabid->department_id,
            'request_id'     => $req->id,
            'type'           => 'request',
            'date'           => today(),
            'created_by'     => $this->admin->id,
        ]);

        // Permintaan sudah selesai → barang boleh di-soft-delete.
        $this->actingAs($this->admin)
            ->delete("/barang/{$this->item->id}")
            ->assertSessionHas('success');

        // Halaman riwayat tidak boleh 500 walau barangnya sudah di Kotak Sampah.
        $this->actingAs($this->admin)->get("/permintaan/{$req->id}")->assertOk();
        $this->actingAs($this->makeUser('petugas_gudang'))->get('/distribusi')->assertOk();
    }

    public function test_pengguna_dengan_riwayat_tidak_bisa_dihapus(): void
    {
        $this->buatPermintaan('pending');

        $this->actingAs($this->admin)
            ->delete("/pengguna/{$this->kabid->id}")
            ->assertSessionHas('error');

        $this->assertNotNull(User::find($this->kabid->id));
    }

    public function test_hapus_permanen_kategori_terpakai_ditangani_ramah(): void
    {
        $kategori = $this->item->category;
        $kategori->delete(); // masuk Kotak Sampah, barang masih mereferensikannya

        $this->actingAs($this->admin)
            ->delete("/sampah/kategori/{$kategori->id}")
            ->assertSessionHas('error');

        $this->assertNotNull(Category::onlyTrashed()->find($kategori->id));
    }

    public function test_purge_melewati_data_yang_masih_dipakai(): void
    {
        $kategori = $this->item->category;
        $kategori->delete();
        $kategori->update(['deleted_at' => now()->subDays(40)]);

        $this->artisan('sampah:purge')->assertSuccessful();

        // Masih direferensikan barang → dilewati, bukan membuat command crash.
        $this->assertNotNull(Category::onlyTrashed()->find($kategori->id));
    }

    public function test_admin_tidak_bisa_mengajukan_permintaan(): void
    {
        $this->actingAs($this->admin)
            ->post('/permintaan', ['items' => [['item_id' => $this->item->id, 'qty' => 5]]])
            ->assertForbidden();
    }

    public function test_kabid_tanpa_bidang_tidak_bisa_mengajukan(): void
    {
        $tanpaBidang = $this->makeUser('kepala_bidang');

        $this->actingAs($tanpaBidang)
            ->post('/permintaan', ['items' => [['item_id' => $this->item->id, 'qty' => 5]]])
            ->assertSessionHas('error');

        $this->assertDatabaseCount('requests', 0);
    }
}
