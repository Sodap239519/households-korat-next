<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_group_id')->constrained('seller_groups')->restrictOnDelete()
                ->comment('กลุ่มเจ้าของสินค้า');
            $table->foreignId('category_id')->nullable()->constrained('product_categories')->nullOnDelete()
                ->comment('หมวดหมู่สินค้า');
            $table->string('name')->comment('ชื่อสินค้า');
            $table->string('slug')->unique()->comment('slug สำหรับ URL');
            $table->string('sku')->nullable()->comment('รหัสสินค้า');
            $table->string('short_description')->nullable()->comment('คำอธิบายสั้น');
            $table->text('description')->nullable()->comment('รายละเอียดสินค้า');
            $table->decimal('price', 10, 2)->default(0)->comment('ราคาปกติ (บาท)');
            $table->decimal('sale_price', 10, 2)->nullable()->comment('ราคาลด (บาท) — null = ไม่ลด');
            $table->unsignedInteger('stock_qty')->default(0)->comment('จำนวนคงเหลือ');
            $table->string('unit', 30)->default('ชิ้น')->comment('หน่วยนับ เช่น กก./ถุง/ชิ้น');
            $table->string('district')->nullable()->comment('อำเภอแหล่งผลิต');
            $table->string('status')->default('draft')->comment('draft|published');
            $table->boolean('is_featured')->default(false)->comment('สินค้าแนะนำ');
            $table->unsignedInteger('view_count')->default(0)->comment('จำนวนการเข้าชม');
            $table->decimal('rating_avg', 3, 2)->default(0)->comment('คะแนนรีวิวเฉลี่ย');
            $table->unsignedInteger('rating_count')->default(0)->comment('จำนวนรีวิว');
            $table->timestamps();
            $table->softDeletes();

            $table->index(['seller_group_id', 'status']);
            $table->index('category_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
