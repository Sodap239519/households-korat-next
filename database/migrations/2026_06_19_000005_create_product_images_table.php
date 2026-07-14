<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete()
                ->comment('สินค้าที่รูปนี้สังกัด');
            $table->string('path')->comment('path รูปใน storage');
            $table->unsignedInteger('sort_order')->default(0)->comment('ลำดับการแสดง');
            $table->boolean('is_primary')->default(false)->comment('เป็นรูปหลักหรือไม่');
            $table->timestamps();

            $table->index('product_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_images');
    }
};
