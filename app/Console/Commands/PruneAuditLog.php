<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use Illuminate\Console\Command;

/**
 * Pangkas audit log lama agar tabel tidak tumbuh tanpa batas.
 *
 * Dua masa simpan berbeda karena nilainya berbeda:
 * - Event autentikasi (login/logout/session_timeout) adalah penyumbang baris
 *   terbanyak dan nilainya cepat basi — cukup 90 hari.
 * - Jejak perubahan data (CRUD) adalah bukti akuntabilitas — simpan 2 tahun.
 */
class PruneAuditLog extends Command
{
    protected $signature = 'audit:prune
                            {--auth-days=90 : Masa simpan event login/logout (hari)}
                            {--days=730 : Masa simpan event lainnya (hari)}';

    protected $description = 'Hapus audit log lama: event autentikasi & jejak perubahan punya masa simpan berbeda.';

    private const AUTH_ACTIVITIES = ['login', 'logout', 'session_timeout'];

    public function handle(): int
    {
        $authDays = max(1, (int) $this->option('auth-days'));
        $days     = max(1, (int) $this->option('days'));

        $auth = AuditLog::whereIn('activity', self::AUTH_ACTIVITIES)
            ->where('created_at', '<', now()->subDays($authDays))
            ->delete();

        $lain = AuditLog::whereNotIn('activity', self::AUTH_ACTIVITIES)
            ->where('created_at', '<', now()->subDays($days))
            ->delete();

        $this->info("Terhapus: {$auth} event autentikasi (> {$authDays} hari), {$lain} event lainnya (> {$days} hari).");

        return self::SUCCESS;
    }
}
