<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->dropColumn('price');
        });

        Schema::table('stock_in_details', function (Blueprint $table) {
            $table->dropColumn('unit_price');
        });
    }

    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            $table->decimal('price', 15, 2)->default(0)->after('reorder_point');
        });

        Schema::table('stock_in_details', function (Blueprint $table) {
            $table->decimal('unit_price', 15, 2)->nullable()->after('quantity');
        });
    }
};
