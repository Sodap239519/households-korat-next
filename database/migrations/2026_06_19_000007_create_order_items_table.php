<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete()
                ->comment('ออเดอร์ที่รายการนี้สังกัด');
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete()
                ->comment('สินค้า (null ได้หากสินค้าถูกลบภายหลัง)');
            $table->string('product_name')->comment('ชื่อสินค้า ณ เวลาสั่งซื้อ (snapshot)');
            $table->decimal('unit_price', 10, 2)->comment('ราคาต่อหน่วย ณ เวลาสั่งซื้อ (snapshot)');
            $table->unsignedInteger('qty')->comment('จำนวนที่สั่ง');
            $table->decimal('line_total', 10, 2)->comment('ยอดรวมรายการ (unit_price × qty)');
            $table->timestamps();

            $table->index('order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
