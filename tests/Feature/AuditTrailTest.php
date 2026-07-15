<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Item;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Jejak audit otomatis (trait Auditable): CRUD master & pengguna tercatat
 * dengan nilai lama→baru, tanpa kebocoran rahasia dan tanpa noise login.
 */
class AuditTrailTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = $this->makeUser('admin');
    }

    private function buatBarang(): Item
    {
        $kategori = Category::create(['name' => 'Kertas', 'description' => '-']);

        return Item::create([
            'code' => 'ATK-A1', 'category_id' => $kategori->id, 'name' => 'Kertas Audit',
            'unit' => 'rim', 'stock' => 10, 'minimum_stock' => 2,
        ]);
    }

    public function test_ubah_barang_tercatat_dengan_nilai_lama_baru(): void
    {
        $item = $this->buatBarang();

        $this->actingAs($this->admin)->put("/barang/{$item->id}", [
            'code' => 'ATK-A1', 'category_id' => $item->category_id, 'name' => 'Kertas Audit Baru',
            'unit' => 'rim', 'minimum_stock' => 2,
        ]);

        $log = AuditLog::where('entity_type', 'items')->where('activity', 'update')->latest('id')->first();

        $this->assertNotNull($log);
        $this->assertSame('Kertas Audit', $log->old_values['name']);
        $this->assertSame('Kertas Audit Baru', $log->new_values['name']);
        $this->assertSame($this->admin->id, $log->user_id);
    }

    public function test_perubahan_stok_tidak_menimbulkan_audit_barang(): void
    {
        $item = $this->buatBarang();

        $item->update(['stock' => 99]); // pergerakan stok punya jejak transaksi sendiri

        $this->assertSame(0, AuditLog::where('entity_type', 'items')->where('activity', 'update')->count());
    }

    public function test_ganti_role_pengguna_tercatat(): void
    {
        $target = $this->makeUser('pimpinan');

        $this->actingAs($this->admin)->put("/pengguna/{$target->id}", [
            'name' => $target->name, 'email' => $target->email,
            'role' => 'petugas_gudang', 'is_active' => 1,
        ]);

        $log = AuditLog::where('entity_type', 'users')->where('activity', 'update')->latest('id')->first();

        $this->assertNotNull($log);
        $this->assertSame('pimpinan', $log->old_values['role']);
        $this->assertSame('petugas_gudang', $log->new_values['role']);
    }

    public function test_password_tidak_pernah_masuk_audit(): void
    {
        $this->actingAs($this->admin)->post('/pengguna', [
            'name' => 'Pegawai Uji', 'email' => 'pegawai.uji@contoh.test',
            'password' => 'rahasia123', 'role' => 'pimpinan', 'is_active' => 1,
        ]);

        $log = AuditLog::where('entity_type', 'users')->where('activity', 'create')->latest('id')->first();

        $this->assertNotNull($log);
        $this->assertArrayNotHasKey('password', $log->new_values);
        $this->assertStringNotContainsString('rahasia123', json_encode($log->new_values));
    }

    public function test_restore_dari_kotak_sampah_tercatat_sekali(): void
    {
        $item = $this->buatBarang();
        $item->delete();

        $this->actingAs($this->admin)->post("/sampah/barang/{$item->id}/restore");

        $this->assertSame(1, AuditLog::where('entity_type', 'items')
            ->where('entity_id', $item->id)
            ->where('activity', 'restore')
            ->count());
    }

    public function test_percobaan_login_gagal_tidak_menjadi_noise_audit(): void
    {
        $this->post('/login', ['email' => $this->admin->email, 'password' => 'salah-total1']);

        // Counter gagal login berubah, tapi tidak boleh tercatat sebagai update user.
        $this->assertSame(0, AuditLog::where('entity_type', 'users')->where('activity', 'update')->count());
    }
}
