<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seller_contact_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('seller_user_id')->constrained('users')->cascadeOnDelete(); // ผู้ส่งคำขอ
            $table->foreignId('seller_group_id')->nullable()->constrained('seller_groups')->nullOnDelete(); // กลุ่มของผู้ขาย
            $table->string('topic', 60);                        // change_zone / change_categories / other
            $table->text('detail');                             // รายละเอียดที่ต้องการ
            $table->enum('status', ['pending', 'in_progress', 'resolved', 'rejected'])->default('pending');
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('admin_note')->nullable();             // หมายเหตุจากแอดมิน
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seller_contact_requests');
    }
};
