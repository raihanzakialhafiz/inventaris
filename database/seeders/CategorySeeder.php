<?php

namespace Database\Seeders;

use App\Models\Category;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            ['id' => 1, 'name' => 'Kertas',        'description' => 'Kertas dan bahan cetak'],
            ['id' => 2, 'name' => 'Alat Tulis',    'description' => 'Pensil, pulpen, spidol, dan sejenisnya'],
            ['id' => 3, 'name' => 'Tinta Printer',  'description' => 'Tinta dan toner printer'],
        ];

        foreach ($categories as $cat) {
            Category::create($cat);
        }
    }
}
