<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Item;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Pengerasan dasar Tahap 1: wildcard LIKE di-escape pada pencarian,
 * dan endpoint uji email SMTP dibatasi laju (throttle).
 */
class KeamananDasarTest extends TestCase
{
    use RefreshDatabase;

    public function test_wildcard_pencarian_dicari_sebagai_teks_biasa(): void
    {
        $admin    = $this->makeUser('admin');
        $kategori = Category::create(['name' => 'Kertas', 'description' => '-']);
        Item::create([
            'code' => 'ATK-W1', 'category_id' => $kategori->id, 'name' => 'Kertas HVS',
            'unit' => 'rim', 'stock' => 10, 'minimum_stock' => 2,
        ]);

        // Kata biasa tetap ketemu.
        $this->actingAs($admin)->get('/barang?search=Kertas')->assertOk()->assertSee('Kertas HVS');

        // "%" dicari sebagai karakter literal — tidak boleh mencocokkan semua data.
        $this->actingAs($admin)->get('/barang?search=%')->assertOk()->assertDontSee('Kertas HVS');
    }

    public function test_header_csp_terpasang(): void
    {
        $csp = $this->get('/login')->headers->get('Content-Security-Policy');

        $this->assertNotNull($csp);
        $this->assertStringContainsString("default-src 'self'", $csp);
        $this->assertStringContainsString("object-src 'none'", $csp);
        $this->assertStringContainsString("form-action 'self'", $csp);
    }

    public function test_backup_gagal_dengan_pesan_jelas_bila_db_bukan_berkas(): void
    {
        // Di lingkungan test database-nya SQLite :memory: — command harus
        // gagal rapi (bukan exception) dan tidak menghapus backup lama.
        $this->artisan('db:backup')->assertFailed();
    }

    public function test_uji_email_dibatasi_laju(): void
    {
        Mail::fake();
        $admin = $this->makeUser('admin');

        for ($i = 1; $i <= 3; $i++) {
            $this->actingAs($admin)
                ->post('/pengaturan/email/test', ['test_email' => 'uji@contoh.test'])
                ->assertSessionHas('success');
        }

        // Permintaan ke-4 dalam menit yang sama ditolak throttle.
        $this->actingAs($admin)
            ->post('/pengaturan/email/test', ['test_email' => 'uji@contoh.test'])
            ->assertStatus(429);
    }
}
