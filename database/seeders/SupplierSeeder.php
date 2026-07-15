<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    public function run(): void
    {
        $suppliers = [
            ['id' => 1, 'name' => 'PT Sinar Mas',    'address' => 'Jl. Sudirman No.1, Jakarta',    'phone' => '021-555-0001', 'email' => 'sales@sinarms.co.id'],
            ['id' => 2, 'name' => 'CV Jaya Mandiri', 'address' => 'Jl. Gajah Mada No.12, Jakarta', 'phone' => '021-555-0002', 'email' => 'info@jayamandiri.id'],
            ['id' => 3, 'name' => 'UD Berkah Abadi', 'address' => 'Jl. Pasar Baru No.5, Bogor',    'phone' => '0251-555-003', 'email' => 'berkah@abadi.id'],
        ];

        foreach ($suppliers as $supplier) {
            Supplier::create($supplier);
        }
    }
}
