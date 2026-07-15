<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Request as ItemRequest;
use App\Models\User;
use App\Support\Notifier;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PersetujuanController extends Controller
{
    public function approve(Request $request, ItemRequest $permintaan)
    {
        if ($permintaan->status !== 'pending') {
            return back()->with('error', 'Permintaan ini sudah diproses.');
        }

        $request->validate([
            'approved_quantities'   => 'required|array',
            'approved_quantities.*' => 'required|integer|min:0|max:100000',
        ]);

        // Validasi seluruh baris SEBELUM menyimpan apa pun — jangan sampai
        // sebagian detail sudah ter-update lalu proses dibatalkan di tengah.
        $quantities = [];
        foreach ($permintaan->details as $detail) {
            $approvedQty = (int) ($request->approved_quantities[$detail->id] ?? 0);
            if ($approvedQty > $detail->quantity_requested) {
                return back()->with('error', 'Jumlah disetujui tidak boleh melebihi jumlah diminta.');
            }
            $quantities[$detail->id] = $approvedQty;
        }

        if (array_sum($quantities) < 1) {
            return back()->with('error', 'Minimal satu barang harus disetujui lebih dari 0. Gunakan Tolak bila seluruh permintaan tidak disetujui.');
        }

        $diproses = DB::transaction(function () use ($permintaan, $quantities) {
            // Kunci baris & cek ulang status — cegah dua kasubag menyetujui bersamaan.
            $terkunci = ItemRequest::whereKey($permintaan->id)->lockForUpdate()->first();
            if ($terkunci->status !== 'pending') {
                return false;
            }

            foreach ($permintaan->details as $detail) {
                $detail->update(['quantity_approved' => $quantities[$detail->id]]);
            }

            $permintaan->update([
                'status'       => 'disetujui',
                'approver_id'  => auth()->id(),
                'approved_date'=> now(),
            ]);

            AuditLog::create([
                'user_id'     => auth()->id(),
                'activity'    => 'approve_request',
                'entity_type' => 'requests',
                'entity_id'   => $permintaan->id,
                'ip_address'  => request()->ip(),
            ]);

            return true;
        });

        if (! $diproses) {
            return back()->with('error', 'Permintaan ini sudah diproses.');
        }

        // Notifikasi in-app + email: ke pemohon & seluruh petugas gudang aktif.
        if ($permintaan->requester) {
            Notifier::toUser(
                $permintaan->requester,
                'request_approved',
                "Permintaan {$permintaan->request_no} Anda telah disetujui oleh " . auth()->user()->name,
                $permintaan->id,
                'requests',
                route('permintaan.show', $permintaan),
                'Lihat Permintaan',
            );
        }

        $gudang = User::where('role', 'petugas_gudang')->where('is_active', true)->get();
        Notifier::toUsers(
            $gudang,
            'ready_to_distribute',
            "Permintaan {$permintaan->request_no} siap didistribusikan.",
            $permintaan->id,
            'requests',
            route('distribusi.index', ['proses' => $permintaan->id]),
            'Proses Distribusi',
        );

        // Kembali ke halaman asal (indeks/detail) agar tidak berpindah konteks.
        return back()->with('success', 'Permintaan berhasil disetujui.');
    }

    public function reject(Request $request, ItemRequest $permintaan)
    {
        if ($permintaan->status !== 'pending') {
            return back()->with('error', 'Permintaan ini sudah diproses.');
        }

        $request->validate([
            'rejection_reason' => 'required|string|min:5|max:500',
        ]);

        $diproses = DB::transaction(function () use ($request, $permintaan) {
            // Kunci baris & cek ulang status — cegah balapan dengan persetujuan.
            $terkunci = ItemRequest::whereKey($permintaan->id)->lockForUpdate()->first();
            if ($terkunci->status !== 'pending') {
                return false;
            }

            $permintaan->update([
                'status'           => 'ditolak',
                'approver_id'      => auth()->id(),
                'approved_date'    => now(),
                'rejection_reason' => $request->rejection_reason,
            ]);

            AuditLog::create([
                'user_id'     => auth()->id(),
                'activity'    => 'reject_request',
                'entity_type' => 'requests',
                'entity_id'   => $permintaan->id,
                'ip_address'  => request()->ip(),
            ]);

            return true;
        });

        if (! $diproses) {
            return back()->with('error', 'Permintaan ini sudah diproses.');
        }

        if ($permintaan->requester) {
            Notifier::toUser(
                $permintaan->requester,
                'request_rejected',
                "Permintaan {$permintaan->request_no} Anda ditolak. Alasan: {$request->rejection_reason}",
                $permintaan->id,
                'requests',
                route('permintaan.show', $permintaan),
                'Lihat Permintaan',
            );
        }

        return back()->with('success', 'Permintaan ditolak.');
    }
}
