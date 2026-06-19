<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_no')->unique()->comment('เลขที่คำสั่งซื้อ');
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete()
                ->comment('ลูกค้าผู้สั่งซื้อ');
            $table->foreignId('seller_group_id')->constrained('seller_groups')->restrictOnDelete()
                ->comment('กลุ่มผู้ขายที่รับผิดชอบออเดอร์นี้');
            // วงจรสถานะ: pending_payment → awaiting_confirm → confirmed → processing
            //            → shipped → delivered → completed (+ cancelled / refund_requested / refunded)
            $table->string('status')->default('pending_payment')->comment('สถานะออเดอร์');
            $table->decimal('subtotal', 10, 2)->default(0)->comment('ยอดรวมสินค้า');
            $table->decimal('shipping_fee', 10, 2)->default(0)->comment('ค่าจัดส่ง');
            $table->decimal('discount', 10, 2)->default(0)->comment('ส่วนลด');
            $table->decimal('total', 10, 2)->default(0)->comment('ยอดสุทธิ');
            $table->string('shipping_name')->comment('ชื่อผู้รับ');
            $table->string('shipping_phone', 30)->comment('เบอร์ผู้รับ');
            $table->string('shipping_address')->comment('ที่อยู่จัดส่ง');
            $table->string('shipping_sub_district')->nullable()->comment('ตำบล');
            $table->string('shipping_district')->nullable()->comment('อำเภอ');
            $table->string('shipping_province')->nullable()->comment('จังหวัด');
            $table->string('shipping_zipcode', 10)->nullable()->comment('รหัสไปรษณีย์');
            $table->text('shipping_note')->nullable()->comment('หมายเหตุการจัดส่ง');
            $table->timestamp('shipped_at')->nullable()->comment('เวลาส่งของ');
            $table->timestamp('delivered_at')->nullable()->comment('เวลาลูกค้ารับของ');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['seller_group_id', 'status']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
