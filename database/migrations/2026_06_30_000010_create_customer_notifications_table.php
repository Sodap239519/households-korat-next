<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete(); // ลูกค้าผู้รับการแจ้งเตือน
            $table->string('type');             // promotion, abandoned_cart, shipping_reminder, shipped
            $table->string('title');            // หัวข้อ
            $table->string('message');          // เนื้อหา
            $table->json('data')->nullable();   // ข้อมูลอ้างอิง (product_id, order_no ฯลฯ)
            $table->string('link')->nullable(); // ลิงก์ที่เกี่ยวข้อง
            $table->timestamp('read_at')->nullable(); // null = ยังไม่อ่าน
            $table->timestamps();

            $table->index(['user_id', 'read_at']); // ค้นหานับยังไม่อ่าน
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_notifications');
    }
};
