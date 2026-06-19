<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete()
                ->comment('ออเดอร์ที่ชำระ');
            $table->decimal('amount', 10, 2)->comment('จำนวนเงินที่แจ้งโอน');
            $table->string('method')->default('bank_transfer')->comment('วิธีชำระเงิน');
            $table->string('slip_path')->nullable()->comment('สลิปการโอน (path ใน storage)');
            $table->string('bank_ref')->nullable()->comment('ธนาคาร/เลขอ้างอิงการโอน');
            $table->timestamp('paid_at')->nullable()->comment('เวลาที่ลูกค้าแจ้งว่าโอน');
            $table->string('status')->default('pending')->comment('pending|confirmed|rejected');
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete()
                ->comment('แอดมินผู้ยืนยัน');
            $table->timestamp('confirmed_at')->nullable()->comment('เวลาที่ยืนยัน');
            $table->text('reject_reason')->nullable()->comment('เหตุผลที่ปฏิเสธ');
            $table->timestamps();

            $table->index('order_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
