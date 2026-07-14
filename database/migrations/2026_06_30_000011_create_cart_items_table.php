<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->string('slug');              // slug snapshot จาก client
            $table->integer('qty')->default(1);  // จำนวนสินค้า
            $table->timestamp('added_at');        // เวลาที่ลูกค้าเพิ่มลงตะกร้า (จาก localStorage)
            $table->timestamps();

            $table->unique(['user_id', 'product_id']); // 1 รายการต่อสินค้าต่อลูกค้า
            $table->index('added_at');            // scheduler ค้นหาตามเวลา
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cart_items');
    }
};
