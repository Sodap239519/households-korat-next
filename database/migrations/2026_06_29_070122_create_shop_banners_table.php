<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('shop_banners', function (Blueprint $table) {
            $table->id();
            $table->string('tag')->nullable()->comment('ป้ายกำกับขนาดเล็กด้านบน');
            $table->string('title')->comment('หัวข้อหลัก (รองรับ HTML เช่น <br>)');
            $table->string('subtitle')->nullable()->comment('คำอธิบายย่อ');
            $table->string('gradient')->default('from-violet-600 via-purple-600 to-fuchsia-600')
                ->comment('Tailwind gradient class สำหรับ background');
            $table->string('cta_label')->default('ดูสินค้า')->comment('ข้อความปุ่ม CTA');
            $table->string('cta_icon')->default('fi fi-rr-shopping-cart')->comment('ไอคอน Flaticon UIcons');
            $table->string('cta_color')->default('bg-orange-500 hover:bg-orange-600')->comment('Tailwind class สีปุ่ม CTA');
            $table->enum('link_type', ['product', 'category', 'group', 'url'])->default('url')
                ->comment('ประเภทลิงก์');
            $table->string('link_value')->nullable()->comment('slug หรือ path ที่ลิงก์ไป');
            $table->json('emojis')->nullable()->comment('อีโมจิ array ตกแต่งฝั่งขวา desktop');
            $table->unsignedTinyInteger('sort_order')->default(0)->comment('ลำดับ (น้อย = แสดงก่อน)');
            $table->boolean('is_active')->default(true)->comment('แสดง/ซ่อน');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shop_banners');
    }
};
