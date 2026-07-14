<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ตัวเลือกสินค้า (เช่น "3 กก.", "5 กก.") — แต่ละตัวเลือกมีราคา+สต๊อกแยกกัน
        Schema::create('product_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('name');                       // ชื่อตัวเลือก เช่น "3 กก."
            $table->decimal('price', 10, 2)->default(0);  // ราคาของตัวเลือกนี้
            $table->integer('stock_qty')->default(0);     // สต๊อกของตัวเลือกนี้
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // อ้างอิงตัวเลือกใน order_items (snapshot ชื่อ/ราคาอยู่ใน product_name/unit_price อยู่แล้ว)
        Schema::table('order_items', function (Blueprint $table) {
            $table->foreignId('product_option_id')->nullable()->after('product_id')
                ->constrained('product_options')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table) {
            $table->dropForeign(['product_option_id']);
            $table->dropColumn('product_option_id');
        });
        Schema::dropIfExists('product_options');
    }
};
