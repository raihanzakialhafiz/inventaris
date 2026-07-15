<?php

namespace Database\Seeders;

use App\Models\Item;
use Illuminate\Database\Seeder;

class ItemSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            ['id' => 1, 'code' => 'ATK-001', 'category_id' => 1, 'name' => 'Kertas A4 70gr',     'unit' => 'rim',   'stock' => 45, 'minimum_stock' => 20, 'location' => 'Rak A1'],
            ['id' => 2, 'code' => 'ATK-002', 'category_id' => 2, 'name' => 'Pulpen Hitam',        'unit' => 'lusin', 'stock' => 30, 'minimum_stock' => 15, 'location' => 'Rak B1'],
            ['id' => 3, 'code' => 'ATK-003', 'category_id' => 3, 'name' => 'Tinta Printer Hitam', 'unit' => 'botol', 'stock' =>  8, 'minimum_stock' => 10, 'location' => 'Rak C2'],
            ['id' => 4, 'code' => 'ATK-004', 'category_id' => 1, 'name' => 'Map Folder',          'unit' => 'pak',   'stock' => 60, 'minimum_stock' => 25, 'location' => 'Rak A2'],
            ['id' => 5, 'code' => 'ATK-005', 'category_id' => 2, 'name' => 'Spidol Whiteboard',   'unit' => 'lusin', 'stock' => 12, 'minimum_stock' => 10, 'location' => 'Rak B2'],
            ['id' => 6, 'code' => 'ATK-006', 'category_id' => 2, 'name' => 'Stapler',             'unit' => 'buah',  'stock' =>  5, 'minimum_stock' =>  8, 'location' => 'Rak B3'],
            ['id' => 7, 'code' => 'ATK-007', 'category_id' => 1, 'name' => 'Kertas F4 70gr',      'unit' => 'rim',   'stock' => 28, 'minimum_stock' => 15, 'location' => 'Rak A1'],
            ['id' => 8, 'code' => 'ATK-008', 'category_id' => 1, 'name' => 'Amplop Coklat',       'unit' => 'pak',   'stock' => 40, 'minimum_stock' => 20, 'location' => 'Rak A3'],
        ];

        foreach ($items as $item) {
            Item::create($item);
        }
    }
}
