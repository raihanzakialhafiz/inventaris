<?php

namespace Tests\Feature;

use App\Models\Category;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PaginationTest extends TestCase
{
    use RefreshDatabase;

    public function test_pagination_muncul_saat_data_melebihi_satu_halaman(): void
    {
        $admin = $this->makeUser('admin');
        for ($i = 1; $i <= 18; $i++) {
            Category::create(['name' => "Kategori {$i}", 'description' => '-']);
        }

        $res = $this->actingAs($admin)->get('/kategori');
        $res->assertOk();
        $res->assertSee('pagination', false);      // ada elemen pagination
        $res->assertSee('page=2', false);          // ada tautan ke halaman 2
    }

    /**
     * Pagination SELALU tampil, juga saat data muat satu halaman — «1» dengan
     * panah nonaktif. Dulu disembunyikan total dan pengguna mengira rusak.
     */
    public function test_pagination_tetap_tampil_saat_data_sedikit(): void
    {
        $admin = $this->makeUser('admin');
        Category::create(['name' => 'Satu', 'description' => '-']);

        $res = $this->actingAs($admin)->get('/kategori');
        $res->assertSee('class="pagination"', false);
        $res->assertDontSee('page=2', false); // tetap tak ada tautan halaman 2
    }
}
