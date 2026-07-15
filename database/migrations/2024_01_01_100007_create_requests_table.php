<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_no', 30)->unique();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->foreignId('department_id')->constrained()->restrictOnDelete();
            $table->date('request_date');
            $table->enum('status', ['pending', 'disetujui', 'ditolak', 'selesai', 'selesai_sebagian'])
                  ->default('pending');
            $table->unsignedBigInteger('approver_id')->nullable();
            $table->foreign('approver_id')->references('id')->on('users')->nullOnDelete();
            $table->timestamp('approved_date')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->boolean('is_flagged')->default(false);
            $table->text('justification')->nullable();
            $table->text('note')->nullable();
            $table->timestamps();
        });

        Schema::create('request_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('request_id')->constrained()->cascadeOnDelete();
            $table->foreignId('item_id')->constrained()->restrictOnDelete();
            $table->unsignedInteger('quantity_requested');
            $table->unsignedInteger('quantity_approved')->nullable();
            $table->unsignedInteger('quantity_distributed')->default(0);
            $table->text('reduction_reason')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('request_details');
        Schema::dropIfExists('requests');
    }
};
