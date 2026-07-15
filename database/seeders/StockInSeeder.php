<?php

namespace Database\Seeders;

use App\Models\StockIn;
use App\Models\StockInDetail;
use App\Models\Item;
use Illuminate\Database\Seeder;

class StockInSeeder extends Seeder
{
    public function run(): void
    {
        $stockIns = [
            [
                'id' => 1, 'transaction_no' => 'BMS-2406-001', 'supplier_id' => 1,
                'date' => '2026-06-01', 'note' => 'Pengadaan awal bulan', 'created_by' => 4,
                'lines' => [
                    ['item_id' => 1, 'quantity' => 20],
                    ['item_id' => 3, 'quantity' => 5],
                ],
            ],
            [
                'id' => 2, 'transaction_no' => 'BMS-2406-002', 'supplier_id' => 2,
                'date' => '2026-06-08', 'note' => 'Pengadaan mingguan', 'created_by' => 4,
                'lines' => [
                    ['item_id' => 2, 'quantity' => 15],
                    ['item_id' => 6, 'quantity' => 10],
                ],
            ],
            [
                'id' => 3, 'transaction_no' => 'BMS-2406-003', 'supplier_id' => 3,
                'date' => '2026-06-12', 'note' => null, 'created_by' => 4,
                'lines' => [
                    ['item_id' => 4, 'quantity' => 30],
                    ['item_id' => 8, 'quantity' => 20],
                ],
            ],
        ];

        foreach ($stockIns as $data) {
            $lines = $data['lines'];
            unset($data['lines']);

            $stockIn = StockIn::create($data);

            foreach ($lines as $line) {
                StockInDetail::create([
                    'stock_in_id' => $stockIn->id,
                    'item_id'     => $line['item_id'],
                    'quantity'    => $line['quantity'],
                ]);
            }
        }
    }
}
