<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mushroom_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quota_id')->constrained('mushroom_quota_districts')->restrictOnDelete();
            $table->foreignId('household_id')->constrained('households')->restrictOnDelete();
            $table->unsignedInteger('bags')->comment('จำนวนถุงที่จัดสรร');
            $table->date('allocated_date')->nullable()->comment('วันที่จัดสรร');
            $table->string('status')->default('pending')->comment('pending|active|completed');
            $table->text('note')->nullable()->comment('หมายเหตุ');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mushroom_allocations');
    }
};
