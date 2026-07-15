<?php

namespace Tests\Feature;

use App\Mail\ActionNotificationMail;
use App\Models\Category;
use App\Models\Item;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class StockAlertEmailTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_stok_menipis_dikirim_ke_admin_gudang_kasubag(): void
    {
        Mail::fake();

        $admin   = $this->makeUser('admin');
        $gudang  = $this->makeUser('petugas_gudang');
        $kasubag = $this->makeUser('kasubag_umum');
        // Peran yang TIDAK termasuk penerima.
        $kabid   = $this->makeUser('kepala_bidang');

        $kategori = Category::create(['name' => 'Kertas', 'description' => '-']);
        // Stok 2 <= minimum 5 → kritis (low_stock).
        Item::create(['code' => 'LOW-1', 'category_id' => $kategori->id, 'name' => 'Kertas Kritis',
            'unit' => 'rim', 'stock' => 2, 'minimum_stock' => 5]);

        $this->artisan('stock:check-minimum')->assertSuccessful();

        foreach ([$admin, $gudang, $kasubag] as $user) {
            Mail::assertQueued(ActionNotificationMail::class, fn ($mail) => $mail->hasTo($user->email));
        }
        // Kepala Bidang tidak menerima email stok.
        Mail::assertNotQueued(ActionNotificationMail::class, fn ($mail) => $mail->hasTo($kabid->email));

        // In-app notifikasi juga terbuat.
        $this->assertDatabaseHas('notifications', ['user_id' => $admin->id, 'type' => 'low_stock']);
    }

    public function test_tidak_kirim_ulang_bila_notifikasi_belum_dibaca(): void
    {
        Mail::fake();

        $this->makeUser('admin');
        $kategori = Category::create(['name' => 'Kertas', 'description' => '-']);
        Item::create(['code' => 'LOW-2', 'category_id' => $kategori->id, 'name' => 'Kertas',
            'unit' => 'rim', 'stock' => 0, 'minimum_stock' => 5]);

        $this->artisan('stock:check-minimum');   // kirim pertama
        $this->artisan('stock:check-minimum');   // seharusnya di-skip (dedup)

        Mail::assertQueuedCount(1);
    }
}
