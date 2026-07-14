<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flash_sale_event_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained('flash_sale_events')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('seller_group_id')->constrained('seller_groups')->cascadeOnDelete();
            $table->decimal('sale_price', 10, 2);                                  // ราคา flash sale
            $table->unsignedInteger('stock_limit')->nullable();                    // จำกัดจำนวน (null=ไม่จำกัด)
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();

            $table->unique(['event_id', 'product_id']);                            // 1 สินค้า/1 event
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flash_sale_event_items');
    }
};
