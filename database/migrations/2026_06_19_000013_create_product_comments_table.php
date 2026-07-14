<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete()
                ->comment('สินค้าที่คอมเมนต์');
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete()
                ->comment('ผู้คอมเมนต์');
            $table->foreignId('parent_id')->nullable()->constrained('product_comments')->cascadeOnDelete()
                ->comment('คอมเมนต์แม่ — null = คอมเมนต์ระดับบนสุด, มีค่า = การตอบกลับ');
            $table->text('body')->comment('เนื้อหาคอมเมนต์/คำถาม');
            $table->string('status')->default('published')->comment('published|hidden');
            $table->timestamps();

            $table->index(['product_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_comments');
    }
};
