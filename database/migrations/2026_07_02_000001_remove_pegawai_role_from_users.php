<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Menyelaraskan enum peran dengan PRD v2.2 (5 peran, tanpa `pegawai`).
 * Aman dijalankan pada DB berjalan: tidak ada baris yang memakai `pegawai`.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Sintaks MySQL-spesifik — dilewati pada driver lain (mis. sqlite saat testing).
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement(
            "ALTER TABLE users MODIFY COLUMN role
             ENUM('admin', 'pimpinan', 'kasubag_umum', 'kepala_bidang', 'petugas_gudang') NOT NULL"
        );
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'mysql') {
            return;
        }

        DB::statement(
            "ALTER TABLE users MODIFY COLUMN role
             ENUM('admin', 'kasubag_umum', 'petugas_gudang', 'kepala_bidang', 'pimpinan', 'pegawai')
             NOT NULL DEFAULT 'pegawai'"
        );
    }
};
