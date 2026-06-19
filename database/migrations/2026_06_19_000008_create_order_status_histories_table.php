<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_status_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete()
                ->comment('ออเดอร์');
            $table->string('status')->comment('สถานะที่เปลี่ยนเป็น');
            $table->text('note')->nullable()->comment('หมายเหตุการเปลี่ยนสถานะ');
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete()
                ->comment('ผู้เปลี่ยนสถานะ');
            $table->timestamp('created_at')->nullable()->comment('เวลาที่เปลี่ยน');

            $table->index('order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_status_histories');
    }
};
