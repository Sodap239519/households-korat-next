<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete()
                ->comment('สินค้าที่รีวิว');
            $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete()
                ->comment('ออเดอร์ที่ใช้ยืนยันการซื้อจริง');
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete()
                ->comment('ผู้รีวิว');
            $table->unsignedTinyInteger('rating')->comment('คะแนน 1-5');
            $table->string('title')->nullable()->comment('หัวข้อรีวิว');
            $table->text('comment')->nullable()->comment('เนื้อหารีวิว');
            $table->json('images')->nullable()->comment('รูปประกอบรีวิว');
            $table->string('status')->default('published')->comment('published|pending|hidden');
            $table->text('reply')->nullable()->comment('คำตอบจากกลุ่มผู้ขาย');
            $table->foreignId('replied_by')->nullable()->constrained('users')->nullOnDelete()
                ->comment('เจ้าหน้าที่ผู้ตอบกลับ');
            $table->timestamps();

            // 1 รีวิวต่อ 1 สินค้า ต่อ 1 ออเดอร์
            $table->unique(['product_id', 'order_id', 'user_id']);
            $table->index(['product_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_reviews');
    }
};
