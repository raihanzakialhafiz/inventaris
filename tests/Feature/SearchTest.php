<?php

namespace Tests\Feature;

use App\Models\Department;
use App\Models\Request as ItemRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_kepala_bidang_tidak_melihat_permintaan_bidang_lain(): void
    {
        $deptA  = Department::create(['code' => 'TIK', 'name' => 'Bidang TIK']);
        $deptB  = Department::create(['code' => 'KEU', 'name' => 'Bidang Keuangan']);
        $kabidA = $this->makeUser('kepala_bidang', $deptA->id);
        $kabidB = $this->makeUser('kepala_bidang', $deptB->id);

        ItemRequest::create([
            'request_no' => 'PRM-9901-777', 'user_id' => $kabidB->id,
            'department_id' => $deptB->id, 'request_date' => today(), 'status' => 'pending',
        ]);

        // Kabid A tidak boleh melihat permintaan bidang B lewat pencarian global.
        $this->actingAs($kabidA)->get('/cari?q=PRM-9901')->assertDontSee('PRM-9901-777');

        // Admin tetap melihatnya.
        $admin = $this->makeUser('admin');
        $this->actingAs($admin)->get('/cari?q=PRM-9901')->assertSee('PRM-9901-777');
    }

    public function test_distribusi_dan_opname_dapat_dicari_petugas_gudang(): void
    {
        $gudang   = $this->makeUser('petugas_gudang');
        $kategori = \App\Models\Category::create(['name' => 'Kertas', 'description' => '-']);
        $item     = \App\Models\Item::create([
            'code' => 'ATK-S01', 'category_id' => $kategori->id, 'name' => 'Kertas Distribusi',
            'unit' => 'rim', 'stock' => 10, 'minimum_stock' => 2,
        ]);

        \App\Models\StockOut::create([
            'transaction_no' => 'BKL-9901-555', 'item_id' => $item->id, 'quantity' => 3,
            'type' => 'request', 'date' => today(), 'created_by' => $gudang->id,
        ]);
        \App\Models\StockOpname::create([
            'opname_no' => 'OPN-9901-444', 'date' => today(), 'created_by' => $gudang->id,
        ]);

        $this->actingAs($gudang)->get('/cari?q=BKL-9901')->assertSee('BKL-9901-555');
        $this->actingAs($gudang)->get('/cari?q=OPN-9901')->assertSee('OPN-9901-444');

        // Kepala Bidang tidak mendapat kedua grup ini (selaras menu & route).
        $dept  = Department::create(['code' => 'TIK', 'name' => 'Bidang TIK']);
        $kabid = $this->makeUser('kepala_bidang', $dept->id);
        $this->actingAs($kabid)->get('/cari?q=BKL-9901')->assertDontSee('BKL-9901-555');
        $this->actingAs($kabid)->get('/cari?q=OPN-9901')->assertDontSee('OPN-9901-444');
    }

    public function test_kepala_bidang_tidak_mendapat_grup_data_barang(): void
    {
        $dept  = Department::create(['code' => 'TIK', 'name' => 'Bidang TIK']);
        $kabid = $this->makeUser('kepala_bidang', $dept->id);

        $kategori = \App\Models\Category::create(['name' => 'Kertas', 'description' => '-']);
        \App\Models\Item::create([
            'code' => 'ATK-Z01', 'category_id' => $kategori->id, 'name' => 'Kertas Rahasia',
            'unit' => 'rim', 'stock' => 5, 'minimum_stock' => 2,
        ]);

        // Grup "Data Barang" tidak muncul untuk kabid (selaras menu & route).
        $this->actingAs($kabid)->get('/cari?q=Kertas')->assertDontSee('ATK-Z01');

        $kasubag = $this->makeUser('kasubag_umum');
        $this->actingAs($kasubag)->get('/cari?q=Kertas')->assertSee('ATK-Z01');
    }
}
