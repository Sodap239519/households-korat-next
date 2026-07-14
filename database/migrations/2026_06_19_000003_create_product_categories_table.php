<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_group_id')->nullable()->constrained('seller_groups')->nullOnDelete()
                ->comment('กลุ่มเจ้าของหมวด — null = หมวดกลางใช้ร่วมกัน');
            $table->foreignId('parent_id')->nullable()->constrained('product_categories')->nullOnDelete()
                ->comment('หมวดแม่ — null = หมวดระดับบนสุด');
            $table->string('name')->comment('ชื่อหมวดหมู่');
            $table->string('slug')->comment('slug สำหรับ URL');
            $table->string('image_path')->nullable()->comment('รูปหมวด');
            $table->unsignedInteger('sort_order')->default(0)->comment('ลำดับการแสดง');
            $table->boolean('is_active')->default(true)->comment('เปิดใช้งานหรือไม่');
            $table->timestamps();

            $table->unique(['seller_group_id', 'slug']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_categories');
    }
};
