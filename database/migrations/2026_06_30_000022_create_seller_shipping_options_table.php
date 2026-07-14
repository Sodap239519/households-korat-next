<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seller_shipping_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_group_id')->constrained('seller_groups')->cascadeOnDelete()
                ->comment('กลุ่มผู้ขายเจ้าของตัวเลือกนี้');
            $table->string('name', 100)->comment('ชื่อบริการจัดส่ง เช่น จัดส่งฟรีโดยผู้ขาย, ไปรษณีย์ EMS');
            $table->decimal('fee', 10, 2)->default(0)->comment('ค่าจัดส่ง (0 = ฟรี)');
            $table->unsignedTinyInteger('days_min')->default(1)->comment('ระยะเวลาขั้นต่ำ (วัน)');
            $table->unsignedTinyInteger('days_max')->default(7)->comment('ระยะเวลาสูงสุด (วัน)');
            $table->boolean('is_default')->default(false)->comment('ตัวเลือกเริ่มต้นที่ลูกค้าเห็น');
            $table->boolean('is_active')->default(true)->comment('เปิดใช้งาน');
            $table->unsignedSmallInteger('sort_order')->default(0)->comment('ลำดับแสดง');
            $table->timestamps();

            $table->index(['seller_group_id', 'is_active', 'sort_order'], 'sso_group_active_sort_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seller_shipping_options');
    }
};
