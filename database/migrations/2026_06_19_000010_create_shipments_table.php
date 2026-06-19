<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete()
                ->comment('ออเดอร์ที่จัดส่ง');
            $table->string('carrier')->nullable()->comment('ผู้ให้บริการขนส่ง เช่น ไปรษณีย์/Flash');
            $table->string('tracking_no')->nullable()->comment('เลขพัสดุ');
            $table->foreignId('shipped_by')->nullable()->constrained('users')->nullOnDelete()
                ->comment('เจ้าหน้าที่ผู้บันทึกการส่ง');
            $table->timestamp('shipped_at')->nullable()->comment('เวลาส่ง');
            $table->string('status')->default('preparing')->comment('preparing|shipped|delivered');
            $table->timestamp('delivered_at')->nullable()->comment('เวลาส่งถึง');
            $table->text('note')->nullable()->comment('หมายเหตุ');
            $table->timestamps();

            $table->index('order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipments');
    }
};
