<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('return_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete()
                ->comment('ออเดอร์ที่ขอคืน/เคลม');
            $table->foreignId('order_item_id')->nullable()->constrained('order_items')->nullOnDelete()
                ->comment('รายการสินค้าที่ขอคืน — null = ทั้งออเดอร์');
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete()
                ->comment('ลูกค้าผู้ขอ');
            $table->string('type')->default('return')->comment('return|refund|claim');
            $table->string('reason')->comment('เหตุผลโดยย่อ');
            $table->text('description')->nullable()->comment('รายละเอียดเพิ่มเติม');
            $table->json('images')->nullable()->comment('รูปประกอบ (paths ใน storage)');
            $table->string('status')->default('requested')->comment('requested|approved|rejected|refunded|completed');
            $table->text('admin_response')->nullable()->comment('คำตอบจากเจ้าหน้าที่');
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete()
                ->comment('เจ้าหน้าที่ผู้จัดการ');
            $table->timestamp('handled_at')->nullable()->comment('เวลาที่จัดการ');
            $table->decimal('refund_amount', 10, 2)->nullable()->comment('จำนวนเงินคืน');
            $table->timestamps();

            $table->index('order_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('return_requests');
    }
};
