<?php

namespace Tests\Feature;

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Tests\TestCase;

class MailSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_simpan_smtp_password_disimpan_terenkripsi(): void
    {
        $admin = $this->makeUser('admin');

        $this->actingAs($admin)->put('/pengaturan/email', [
            'mail_from_address'         => 'akun@gmail.com',
            'mail_from_name'            => 'Sistem',
            'mail_password'             => 'rahasiakuat123',
            'stock_alert_interval_days' => 2,
            'stock_alert_hour'          => 8,
        ])->assertSessionHasNoErrors();

        $stored = Setting::where('key', 'mail_password')->value('value');
        $this->assertNotSame('rahasiakuat123', $stored, 'Password tidak boleh tersimpan plain');
        $this->assertSame('rahasiakuat123', Crypt::decryptString($stored));

        $this->assertDatabaseHas('settings', ['key' => 'mail_from_address', 'value' => 'akun@gmail.com']);
        $this->assertDatabaseHas('settings', ['key' => 'stock_alert_interval_days', 'value' => '2']);
    }

    public function test_password_kosong_tidak_menimpa_yang_lama(): void
    {
        $admin = $this->makeUser('admin');
        Setting::updateOrCreate(['key' => 'mail_password'], ['value' => Crypt::encryptString('lama123'), 'type' => 'secret']);

        $this->actingAs($admin)->put('/pengaturan/email', [
            'mail_from_address' => 'akun@gmail.com', 'mail_password' => '',
            'stock_alert_interval_days' => 1, 'stock_alert_hour' => 7,
        ]);

        $stored = Setting::where('key', 'mail_password')->value('value');
        $this->assertSame('lama123', Crypt::decryptString($stored));
    }

    public function test_non_admin_diblokir(): void
    {
        $this->actingAs($this->makeUser('kepala_bidang'))->get('/pengaturan/email')->assertForbidden();
    }
}
