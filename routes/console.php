<?php

use App\Models\Setting;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Schema;

// Jadwal reminder stok menipis — dapat diatur admin (Pengaturan Email):
// setiap N hari pada jam H. Default: setiap hari jam 07:00.
$alertDays = 1;
$alertHour = 7;
if (Schema::hasTable('settings')) {
    $alertDays = max(1, (int) setting('stock_alert_interval_days', 1));
    $alertHour = min(23, max(0, (int) setting('stock_alert_hour', 7)));
}
$alertTime = sprintf('%02d:00', $alertHour);

if ($alertDays <= 1) {
    Schedule::command('stock:check-minimum')->dailyAt($alertTime)->runInBackground();
} else {
    // Dicek harian, dikirim bila sudah N hari sejak kiriman terakhir.
    // (Cron */N tidak dipakai: hitungannya ter-reset tiap awal bulan.)
    Schedule::command('stock:check-minimum')
        ->dailyAt($alertTime)
        ->when(function () use ($alertDays) {
            $terakhir = setting('stock_alert_last_sent');

            return ! $terakhir || Carbon::parse($terakhir)->addDays($alertDays)->startOfDay()->lte(today());
        })
        ->onSuccess(fn () => Setting::updateOrCreate(
            ['key' => 'stock_alert_last_sent'],
            ['value' => today()->toDateString(), 'type' => 'text'],
        ))
        ->runInBackground();
}

// Bersihkan Kotak Sampah: hapus permanen data soft-deleted > 30 hari.
Schedule::command('sampah:purge')->dailyAt('02:00')->runInBackground();

// Backup database harian (retensi 14 hari) — lihat app/Console/Commands/BackupDatabase.
Schedule::command('db:backup')->dailyAt('01:30')->runInBackground();

// Pangkas audit log: event login/logout 90 hari, jejak perubahan data 2 tahun —
// lihat app/Console/Commands/PruneAuditLog.
Schedule::command('audit:prune')->dailyAt('02:15')->runInBackground();
