<?php

namespace Database\Seeders;

use App\Models\Request as ItemRequest;
use App\Models\RequestDetail;
use App\Models\StockOut;
use Illuminate\Database\Seeder;

class RequestSeeder extends Seeder
{
    public function run(): void
    {
        $requests = [
            [
                'id' => 101, 'request_no' => 'PRM-2406-001', 'user_id' => 2, 'department_id' => 3,
                'request_date' => '2026-06-02', 'status' => 'selesai', 'approver_id' => 3,
                'approved_date' => '2026-06-02 10:00:00', 'is_flagged' => false,
                'justification' => null, 'note' => 'Kebutuhan rutin awal bulan',
                'lines' => [
                    ['item_id' => 1, 'quantity_requested' => 8, 'quantity_approved' => 8, 'quantity_distributed' => 8, 'reduction_reason' => null],
                    ['item_id' => 3, 'quantity_requested' => 2, 'quantity_approved' => 2, 'quantity_distributed' => 2, 'reduction_reason' => null],
                ],
            ],
            [
                'id' => 102, 'request_no' => 'PRM-2406-002', 'user_id' => 6, 'department_id' => 1,
                'request_date' => '2026-06-03', 'status' => 'selesai', 'approver_id' => 3,
                'approved_date' => '2026-06-03 09:00:00', 'is_flagged' => false,
                'justification' => null, 'note' => null,
                'lines' => [
                    ['item_id' => 1, 'quantity_requested' => 6, 'quantity_approved' => 6, 'quantity_distributed' => 6, 'reduction_reason' => null],
                    ['item_id' => 4, 'quantity_requested' => 5, 'quantity_approved' => 5, 'quantity_distributed' => 5, 'reduction_reason' => null],
                ],
            ],
            [
                'id' => 103, 'request_no' => 'PRM-2406-003', 'user_id' => 7, 'department_id' => 2,
                'request_date' => '2026-06-05', 'status' => 'selesai_sebagian', 'approver_id' => 3,
                'approved_date' => '2026-06-05 11:00:00', 'is_flagged' => false,
                'justification' => null, 'note' => null,
                'lines' => [
                    ['item_id' => 6, 'quantity_requested' => 3, 'quantity_approved' => 3, 'quantity_distributed' => 2, 'reduction_reason' => 'Stok stapler menipis, dipenuhi sebagian'],
                ],
            ],
            [
                'id' => 104, 'request_no' => 'PRM-2406-004', 'user_id' => 2, 'department_id' => 3,
                'request_date' => '2026-06-10', 'status' => 'pending', 'approver_id' => null,
                'approved_date' => null, 'is_flagged' => true,
                'justification' => 'Persiapan rapat koordinasi besar akhir bulan.', 'note' => null,
                'lines' => [
                    ['item_id' => 1, 'quantity_requested' => 9, 'quantity_approved' => null, 'quantity_distributed' => 0, 'reduction_reason' => null],
                ],
            ],
            [
                'id' => 105, 'request_no' => 'PRM-2406-005', 'user_id' => 6, 'department_id' => 1,
                'request_date' => '2026-06-11', 'status' => 'disetujui', 'approver_id' => 3,
                'approved_date' => '2026-06-11 08:30:00', 'is_flagged' => false,
                'justification' => null, 'note' => null,
                'lines' => [
                    ['item_id' => 1, 'quantity_requested' => 4, 'quantity_approved' => 4, 'quantity_distributed' => 0, 'reduction_reason' => null],
                    ['item_id' => 2, 'quantity_requested' => 2, 'quantity_approved' => 2, 'quantity_distributed' => 0, 'reduction_reason' => null],
                ],
            ],
            [
                'id' => 106, 'request_no' => 'PRM-2406-006', 'user_id' => 8, 'department_id' => 4,
                'request_date' => '2026-06-09', 'status' => 'pending', 'approver_id' => null,
                'approved_date' => null, 'is_flagged' => false,
                'justification' => null, 'note' => null,
                'lines' => [
                    ['item_id' => 8, 'quantity_requested' => 4, 'quantity_approved' => null, 'quantity_distributed' => 0, 'reduction_reason' => null],
                    ['item_id' => 5, 'quantity_requested' => 2, 'quantity_approved' => null, 'quantity_distributed' => 0, 'reduction_reason' => null],
                ],
            ],
        ];

        $stockOutSeq = 1;

        foreach ($requests as $data) {
            $lines = $data['lines'];
            unset($data['lines']);

            $request = ItemRequest::create($data);

            foreach ($lines as $line) {
                RequestDetail::create(array_merge($line, ['request_id' => $request->id]));

                if ($line['quantity_distributed'] > 0) {
                    StockOut::create([
                        'transaction_no' => 'BKL-2406-' . str_pad($stockOutSeq++, 3, '0', STR_PAD_LEFT),
                        'item_id'        => $line['item_id'],
                        'quantity'       => $line['quantity_distributed'],
                        'department_id'  => $data['department_id'],
                        'request_id'     => $request->id,
                        'type'           => 'request',
                        'date'           => $data['request_date'],
                        'created_by'     => 4,
                    ]);
                }
            }
        }
    }
}
