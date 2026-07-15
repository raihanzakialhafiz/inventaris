<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Department;
use App\Models\Item;
use App\Models\Notification;
use App\Models\Request as ItemRequest;
use App\Models\StockOut;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tahap 5: badge notifikasi live (endpoint hitung), notifikasi-read aman
 * dari prefetch browser, dan grafik tren tampil di dashboard.
 */
class NotifikasiLiveTest extends TestCase
{
    use RefreshDatabase;

    public function test_endpoint_hitung_mengembalikan_jumlah_belum_terbaca(): void
    {
        $user = $this->makeUser('admin');
        Notification::create(['user_id' => $user->id, 'type' => 'x', 'message' => 'satu', 'is_read' => false]);
        Notification::create(['user_id' => $user->id, 'type' => 'x', 'message' => 'dua', 'is_read' => true]);
        // Milik pengguna lain — tidak boleh terhitung.
        $lain = $this->makeUser('pimpinan');
        Notification::create(['user_id' => $lain->id, 'type' => 'x', 'message' => 'tiga', 'is_read' => false]);

        $this->actingAs($user)->get('/notifikasi/hitung')->assertOk()->assertJson(['unread' => 1]);
    }

    public function test_dropdown_navbar_hanya_tampilkan_yang_belum_dibaca(): void
    {
        $user = $this->makeUser('admin');
        Notification::create(['user_id' => $user->id, 'type' => 'x', 'message' => 'PESAN-BELUM-DIBACA', 'is_read' => false]);
        Notification::create(['user_id' => $user->id, 'type' => 'x', 'message' => 'PESAN-SUDAH-DIBACA', 'is_read' => true]);

        // Dropdown notifikasi ada di layout tiap halaman → cek lewat /dashboard.
        $this->actingAs($user)->get('/dashboard')
            ->assertOk()
            ->assertSee('PESAN-BELUM-DIBACA')
            ->assertDontSee('PESAN-SUDAH-DIBACA');
    }

    public function test_prefetch_tidak_menandai_notifikasi_terbaca(): void
    {
        $user = $this->makeUser('admin');
        $n = Notification::create(['user_id' => $user->id, 'type' => 'x', 'message' => 'm', 'is_read' => false]);

        // Prefetch/prerender browser — bukan klik pengguna.
        $this->actingAs($user)->get(route('notifikasi.read', $n), ['Sec-Purpose' => 'prefetch']);
        $this->assertFalse((bool) $n->fresh()->is_read);

        // Klik sungguhan — barulah terbaca.
        $this->actingAs($user)->get(route('notifikasi.read', $n));
        $this->assertTrue((bool) $n->fresh()->is_read);
    }

    public function test_dashboard_admin_menampilkan_grafik_tren(): void
    {
        $admin    = $this->makeUser('admin');
        $kategori = Category::create(['name' => 'Kertas', 'description' => '-']);
        $item     = Item::create([
            'code' => 'ATK-C1', 'category_id' => $kategori->id, 'name' => 'Kertas Grafik',
            'unit' => 'rim', 'stock' => 50, 'minimum_stock' => 5,
        ]);
        StockOut::create([
            'transaction_no' => 'BKL-TST-C1', 'item_id' => $item->id, 'quantity' => 7,
            'type' => 'request', 'date' => today(), 'created_by' => $admin->id,
        ]);

        $this->actingAs($admin)->get('/dashboard')
            ->assertOk()
            ->assertSee('Tren Barang Keluar')
            ->assertSee('<svg', false);
    }

    public function test_dashboard_kasubag_menampilkan_grafik_permintaan(): void
    {
        $dept  = Department::create(['code' => 'TIK', 'name' => 'Bidang TIK']);
        $kabid = $this->makeUser('kepala_bidang', $dept->id);
        ItemRequest::create([
            'request_no' => 'PRM-TST-C1', 'user_id' => $kabid->id, 'department_id' => $dept->id,
            'request_date' => today(), 'status' => 'pending',
        ]);

        $this->actingAs($this->makeUser('kasubag_umum'))->get('/dashboard')
            ->assertOk()
            ->assertSee('Tren Permintaan Masuk')
            ->assertSee('<svg', false);
    }
}
