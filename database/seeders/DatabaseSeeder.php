<?php

namespace Database\Seeders;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Seeder perlu menetapkan `id` eksplisit (referensi antar-seeder),
        // sedangkan `id` sengaja TIDAK fillable di model (anti mass-assignment).
        Model::unguard();

        $this->call([
            SettingSeeder::class,
            UserSeeder::class,
            DepartmentSeeder::class,
            CategorySeeder::class,
            UnitSeeder::class,
            SupplierSeeder::class,
            ItemSeeder::class,
            StockInSeeder::class,
            RequestSeeder::class,
        ]);

        Model::reguard();
    }
}
