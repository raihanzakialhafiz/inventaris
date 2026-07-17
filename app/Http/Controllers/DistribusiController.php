<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Models\Request as ItemRequest;
use App\Models\StockOut;
use App\Models\AuditLog;
use App\Support\Notifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class DistribusiController extends Controller
{
    public function index(Request $request)
    {
        // Nama halaman 'antrean' agar tidak bentrok dengan 'page' milik riwayat.
        $approved = ItemRequest::with(['department', 'requester', 'details.item'])
            ->where('status', 'disetujui')
            ->latest()
            ->paginate(10, ['*'], 'antrean')
            ->withQueryString();

        $perPage = in_array((int) $request->per_page, [10, 25, 50, 100]) ? (int) $request->per_page : 15;

        $history = StockOut::with(['item', 'department', 'request', 'createdBy'])
            ->where('type', 'request')
            ->latest()
            ->paginate($perPage)
            ->withQueryString();

        return view('distribusi.index', compact('approved', 'history'));
    }

    public function store(Request $request, ItemRequest $permintaan)
    {
        if ($permintaan->status !== 'disetujui') {
            return redirect()->route('distribusi.index')
                ->with('error', 'Permintaan ini tidak dalam status disetujui.');
        }

        $validator = Validator::make($request->all(), [
            'distributions'                    => 'required|array',
            'distributions.*.qty'              => 'required|integer|min:0|max:100000',
            'distributions.*.reduction_reason' => 'nullable|string|max:255',
        ], [
            'distributions.required'       => 'Data distribusi tidak boleh kosong.',
            'distributions.*.qty.required' => 'Jumlah distribusi wajib diisi.',
        ]);

        // Gagal validasi → kembali ke daftar & buka kembali modal permintaan ini.
        if ($validator->fails()) {
            return redirect()->route('distribusi.index', ['proses' => $permintaan->id])
                ->withErrors($validator)->withInput();
        }

        try {
            $newStatus = DB::transaction(function () use ($request, $permintaan) {
                // Kunci baris permintaan & cek ulang status di dalam transaksi —
                // dua petugas yang memproses bersamaan tidak boleh mendistribusikan dua kali.
                $terkunci = ItemRequest::whereKey($permintaan->id)->lockForUpdate()->firstOrFail();
                if ($terkunci->status !== 'disetujui') {
                    throw new \Exception('Permintaan ini sudah diproses oleh pengguna lain.');
                }

                $month   = now()->format('ym');
                // lockForUpdate menyerialisasi penomoran BKL antar transaksi bersamaan.
                $seq     = StockOut::where('transaction_no', 'like', "BKL-{$month}-%")->lockForUpdate()->count();
                $allDone = true;

                foreach ($permintaan->details as $detail) {
                    $dist   = (int) ($request->distributions[$detail->id]['qty'] ?? 0);
                    $reason = $request->distributions[$detail->id]['reduction_reason'] ?? null;

                    if ($dist < 0 || $dist > $detail->quantity_approved) {
                        throw new \Exception("Jumlah distribusi tidak valid untuk {$detail->item->name}");
                    }

                    if ($dist < $detail->quantity_approved) {
                        $allDone = false;
                        if (empty($reason)) {
                            throw new \Exception("Alasan wajib diisi jika distribusi dikurangi untuk {$detail->item->name}");
                        }
                    }

                    if ($dist > 0) {
                        // Kunci baris barang & cek stok AKTUAL — stok bisa berubah sejak disetujui.
                        $item = Item::whereKey($detail->item_id)->lockForUpdate()->firstOrFail();
                        if ($dist > $item->stock) {
                            throw new \Exception("Stok {$item->name} tidak mencukupi (tersisa {$item->stock}). Kurangi jumlah dan isi alasan.");
                        }

                        $seq++;
                        $no = "BKL-{$month}-" . str_pad($seq, 3, '0', STR_PAD_LEFT);

                        StockOut::create([
                            'transaction_no' => $no,
                            'item_id'        => $detail->item_id,
                            'quantity'       => $dist,
                            'department_id'  => $permintaan->department_id,
                            'request_id'     => $permintaan->id,
                            'type'           => 'request',
                            'date'           => today(),
                            'created_by'     => auth()->id(),
                        ]);

                        $item->decrement('stock', $dist);
                    }

                    // Simpan hasil untuk SEMUA baris — termasuk qty 0, agar
                    // alasan pembatalan/pengurangan tetap terekam.
                    $detail->update([
                        'quantity_distributed' => $dist,
                        'reduction_reason'     => $dist < $detail->quantity_approved ? $reason : null,
                    ]);
                }

                $newStatus = $allDone ? 'selesai' : 'selesai_sebagian';
                $permintaan->update(['status' => $newStatus]);

                AuditLog::create([
                    'user_id'     => auth()->id(),
                    'activity'    => 'distribute_request',
                    'entity_type' => 'requests',
                    'entity_id'   => $permintaan->id,
                    'new_values'  => ['status' => $newStatus],
                    'ip_address'  => request()->ip(),
                ]);

                return $newStatus;
            });
        } catch (\Throwable $e) {
            return redirect()->route('distribusi.index', ['proses' => $permintaan->id])
                ->with('error', $e->getMessage());
        }

        // Notifikasi in-app + email ke pemohon (setelah commit).
        if ($permintaan->requester) {
            Notifier::toUser(
                $permintaan->requester,
                'request_distributed',
                "Permintaan {$permintaan->request_no} telah didistribusikan (" . ($newStatus === 'selesai' ? 'penuh' : 'sebagian') . ").",
                $permintaan->id,
                'requests',
                route('permintaan.show', $permintaan),
                'Lihat Permintaan',
            );
        }

        return redirect()->route('distribusi.index')->with('success', 'Distribusi berhasil dicatat, stok diperbarui.');
    }
}
