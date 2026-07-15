<?php

namespace Database\Seeders;

use App\Models\Unit;
use Illuminate\Database\Seeder;

class UnitSeeder extends Seeder
{
    public function run(): void
    {
        $units = [
            ['name' => 'rim',   'description' => 'Kertas (500 lembar)'],
            ['name' => 'lusin', 'description' => '12 buah'],
            ['name' => 'buah',  'description' => 'Satuan unit'],
            ['name' => 'pak',   'description' => 'Kemasan pak'],
            ['name' => 'box',   'description' => 'Kemasan box'],
            ['name' => 'botol', 'description' => 'Kemasan botol'],
            ['name' => 'pcs',   'description' => 'Pieces'],
            ['name' => 'set',   'description' => 'Satu set'],
            ['name' => 'roll',  'description' => 'Gulungan'],
        ];

        foreach ($units as $u) {
            Unit::updateOrCreate(['name' => $u['name']], $u);
        }
    }
}
