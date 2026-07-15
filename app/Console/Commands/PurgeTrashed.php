<?php

namespace App\Console\Commands;

use App\Http\Controllers\SampahController;
use App\Models\Category;
use App\Models\Department;
use App\Models\Item;
use App\Models\Supplier;
use App\Models\Unit;
use Illuminate\Console\Command;

/**
 * Membersihkan Kotak Sampah: hapus permanen data soft-deleted yang lebih tua
 * dari N hari (default = masa retensi Kotak Sampah, 30 hari).
 */
class PurgeTrashed extends Command
{
    protected $signature = 'sampah:purge {--days= : Ambang usia (hari) sebelum dihapus permanen}';

    protected $description = 'Hapus permanen data Kotak Sampah yang melewati masa retensi.';

    private const MODELS = [Item::class, Category::class, Unit::class, Department::class, Supplier::class];

    public function handle(): int
    {
        $days    = (int) ($this->option('days') ?: SampahController::RETENTION_DAYS);
        $cutoff  = now()->subDays($days);
        $total   = 0;
        $skipped = 0;

        foreach (self::MODELS as $model) {
            // Per-baris: satu data yang masih direferensikan FK (restrictOnDelete)
            // tidak boleh menggagalkan pembersihan sisanya.
            foreach ($model::onlyTrashed()->where('deleted_at', '<', $cutoff)->get() as $rec) {
                try {
                    $rec->forceDelete();
                    $total++;
                } catch (\Illuminate\Database\QueryException) {
                    $skipped++;
                }
            }
        }

        $this->info("Kotak Sampah dibersihkan: {$total} data (>{$days} hari) dihapus permanen, {$skipped} dilewati (masih dipakai riwayat).");

        return self::SUCCESS;
    }
}
