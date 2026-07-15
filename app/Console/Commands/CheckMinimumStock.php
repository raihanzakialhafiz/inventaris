<?php

namespace App\Console\Commands;

use App\Models\Item;
use App\Models\Notification;
use App\Models\User;
use App\Support\Notifier;
use Illuminate\Console\Command;

/**
 * Reminder stok minimum (PRD §6.17).
 * Memindai barang yang perlu perhatian lalu mengirim notifikasi in-app + EMAIL
 * (`low_stock` untuk stok kritis, `reorder` untuk peringatan dini) ke Admin,
 * Petugas Gudang, dan Kasubag Umum. Dijalankan harian lewat Scheduler.
 */
class CheckMinimumStock extends Command
{
    protected $signature = 'stock:check-minimum';

    protected $description = 'Kirim notifikasi stok kritis (low_stock) & peringatan dini pemesanan ulang (reorder)';

    public function handle(): int
    {
        $needsAttention = Item::get()->filter->needsReorder();

        if ($needsAttention->isEmpty()) {
            $this->info('Semua stok aman — tidak ada notifikasi.');
            return self::SUCCESS;
        }

        $recipients = User::whereIn('role', ['admin', 'petugas_gudang', 'kasubag_umum'])
            ->where('is_active', true)
            ->get();

        $url     = route('barang.index');
        $created = 0;

        foreach ($needsAttention as $item) {
            // low_stock = sudah menyentuh minimum; reorder = masih di zona peringatan dini.
            $isCritical = $item->stock <= $item->minimum_stock;
            $type       = $isCritical ? 'low_stock' : 'reorder';
            $message    = $isCritical
                ? "Stok {$item->name} ({$item->code}) tinggal {$item->stock} {$item->unit} — di bawah minimum {$item->minimum_stock}."
                : "Stok {$item->name} ({$item->code}) menyentuh ambang pesan ulang ({$item->stock} {$item->unit}). Saran pesan +{$item->suggestedReorderQty()}.";

            foreach ($recipients as $user) {
                // Hindari spam: lewati bila sudah ada notifikasi tipe sama yang belum dibaca.
                $alreadyNotified = Notification::where('user_id', $user->id)
                    ->where('type', $type)
                    ->where('reference_type', 'items')
                    ->where('reference_id', $item->id)
                    ->where('is_read', false)
                    ->exists();

                if ($alreadyNotified) {
                    continue;
                }

                // In-app + email (dedup di atas mencegah email berulang).
                Notifier::toUser($user, $type, $message, $item->id, 'items', $url, 'Lihat Barang');
                $created++;
            }
        }

        $this->info("Selesai. {$needsAttention->count()} barang perlu perhatian, {$created} notifikasi dibuat.");

        return self::SUCCESS;
    }
}
