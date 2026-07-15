<?php

namespace Tests\Feature;

use App\Mail\ActionNotificationMail;
use App\Models\Category;
use App\Models\Department;
use App\Models\Item;
use App\Models\Request as ItemRequest;
use App\Models\RequestDetail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class NotificationEmailTest extends TestCase
{
    use RefreshDatabase;

    public function test_persetujuan_mengirim_email_ke_pemohon(): void
    {
        Mail::fake();

        $dept  = Department::create(['code' => 'TIK', 'name' => 'Bidang TIK']);
        $kabid = $this->makeUser('kepala_bidang', $dept->id);
        $kasubag = $this->makeUser('kasubag_umum');

        $kategori = Category::create(['name' => 'Kertas', 'description' => '-']);
        $item = Item::create(['code' => 'N-1', 'category_id' => $kategori->id, 'name' => 'Kertas', 'unit' => 'rim', 'stock' => 100, 'minimum_stock' => 5]);

        $req = ItemRequest::create([
            'request_no' => 'PRM-0001-001', 'user_id' => $kabid->id, 'department_id' => $dept->id,
            'request_date' => today(), 'status' => 'pending',
        ]);
        $detail = RequestDetail::create(['request_id' => $req->id, 'item_id' => $item->id, 'quantity_requested' => 5]);

        $this->actingAs($kasubag)
            ->from('/permintaan')
            ->post("/permintaan/{$req->id}/approve", ['approved_quantities' => [$detail->id => 5]]);

        // Email dikirim ke pemohon (Kepala Bidang).
        Mail::assertQueued(ActionNotificationMail::class, fn ($mail) => $mail->hasTo($kabid->email));
    }

    public function test_pengajuan_mengirim_email_ke_kasubag(): void
    {
        Mail::fake();

        $dept  = Department::create(['code' => 'TIK', 'name' => 'Bidang TIK']);
        $kabid = $this->makeUser('kepala_bidang', $dept->id);
        $kasubag = $this->makeUser('kasubag_umum');

        $kategori = Category::create(['name' => 'Kertas', 'description' => '-']);
        $item = Item::create(['code' => 'N-2', 'category_id' => $kategori->id, 'name' => 'Kertas', 'unit' => 'rim', 'stock' => 100, 'minimum_stock' => 5]);

        $this->actingAs($kabid)->post('/permintaan', ['items' => [['item_id' => $item->id, 'qty' => 3]]]);

        Mail::assertQueued(ActionNotificationMail::class, fn ($mail) => $mail->hasTo($kasubag->email));
    }
}
