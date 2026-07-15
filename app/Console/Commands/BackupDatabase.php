<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;

/**
 * Backup database ke storage/app/backups dengan retensi N hari.
 * MySQL/MariaDB memakai mysqldump (path bisa diatur via env MYSQLDUMP_PATH);
 * SQLite cukup menyalin berkas database. Dijadwalkan harian oleh Scheduler.
 */
class BackupDatabase extends Command
{
    protected $signature = 'db:backup {--keep=14 : Usia maksimum backup (hari) sebelum dihapus}';

    protected $description = 'Cadangkan database ke storage/app/backups (retensi otomatis).';

    public function handle(): int
    {
        $conn = config('database.default');
        $cfg  = config("database.connections.{$conn}", []);
        $dir  = storage_path('app/backups');
        File::ensureDirectoryExists($dir);

        $file   = $dir . DIRECTORY_SEPARATOR . 'backup-' . now()->format('Ymd-His');
        $driver = $cfg['driver'] ?? '?';

        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            $ok = $this->dumpMysql($cfg, $file .= '.sql');
        } elseif ($driver === 'sqlite') {
            $ok = $this->copySqlite($cfg, $file .= '.sqlite');
        } else {
            $this->error("Driver database '{$driver}' belum didukung perintah backup.");
            $ok = false;
        }

        if (! $ok) {
            return self::FAILURE;
        }

        // Retensi hanya berjalan setelah backup baru BERHASIL — jangan pernah
        // menghapus cadangan lama ketika pembuatan yang baru gagal.
        $dihapus = $this->applyRetention($dir, max(1, (int) $this->option('keep')));

        $this->info('Backup tersimpan: ' . basename($file) . " ({$dihapus} backup lama dihapus).");

        return self::SUCCESS;
    }

    private function dumpMysql(array $cfg, string $file): bool
    {
        $proc = new Process([
            env('MYSQLDUMP_PATH', 'mysqldump'),
            '--host=' . ($cfg['host'] ?? '127.0.0.1'),
            '--port=' . ($cfg['port'] ?? '3306'),
            '--user=' . ($cfg['username'] ?? 'root'),
            '--single-transaction',
            '--routines',
            '--result-file=' . $file,
            $cfg['database'] ?? '',
            // Password lewat env MYSQL_PWD agar tidak terlihat di daftar proses.
        ], null, ['MYSQL_PWD' => $cfg['password'] ?? '']);

        $proc->setTimeout(600)->run();

        if (! $proc->isSuccessful()) {
            File::delete($file);
            $this->error('mysqldump gagal: ' . trim($proc->getErrorOutput() ?: $proc->getOutput()));

            return false;
        }

        return true;
    }

    private function copySqlite(array $cfg, string $file): bool
    {
        $db = (string) ($cfg['database'] ?? '');

        if ($db === ':memory:' || ! is_file($db)) {
            $this->error('Database SQLite bukan berkas di disk — tidak ada yang bisa dicadangkan.');

            return false;
        }

        return File::copy($db, $file);
    }

    /** Hapus backup yang lebih tua dari N hari; kembalikan jumlah yang dihapus. */
    private function applyRetention(string $dir, int $keepDays): int
    {
        $batas   = now()->subDays($keepDays)->getTimestamp();
        $dihapus = 0;

        foreach (File::files($dir) as $f) {
            if (str_starts_with($f->getFilename(), 'backup-') && $f->getMTime() < $batas) {
                File::delete($f->getPathname());
                $dihapus++;
            }
        }

        return $dihapus;
    }
}
