<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * department_id boleh NULL → kuota berlaku untuk SEMUA bidang (global).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('request_quotas', function (Blueprint $table) {
            $table->foreignId('department_id')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('request_quotas', function (Blueprint $table) {
            $table->foreignId('department_id')->nullable(false)->change();
        });
    }
};
