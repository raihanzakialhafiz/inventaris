<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            ['key' => 'app_name',         'value' => 'SIIB',                          'type' => 'text'],
            ['key' => 'institution_name', 'value' => 'Inventaris Barang',             'type' => 'text'],
            ['key' => 'logo',             'value' => null,                            'type' => 'image'],
            ['key' => 'favicon',          'value' => null,                            'type' => 'image'],
            ['key' => 'login_image',      'value' => null,                            'type' => 'image'],
            ['key' => 'address',          'value' => 'Jl. Contoh No. 1, Kota',        'type' => 'text'],
            ['key' => 'footer_text',      'value' => '© 2026 Sistem Inventaris ATK',  'type' => 'text'],
            ['key' => 'contact_email',    'value' => 'info@instansi.go.id',           'type' => 'email'],
            ['key' => 'session_timeout',  'value' => '30',                            'type' => 'number'],
        ];

        foreach ($defaults as $s) {
            Setting::updateOrCreate(['key' => $s['key']], $s);
        }
    }
}
