<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * NIP & jabatan resmi — dipakai blok tanda tangan pada laporan PDF/Excel.
 *
 * Keduanya nullable: akun lama belum punya nilai, dan tidak semua peran
 * (mis. akun uji) perlu menandatangani laporan.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Peran sistem (role) bukan jabatan resmi: naskah dinas menuntut
            // jabatan sebenarnya, mis. "Kepala Dinas", bukan "Pimpinan".
            $table->string('nip', 30)->nullable()->after('email');
            $table->string('jabatan')->nullable()->after('nip');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['nip', 'jabatan']);
        });
    }
};
