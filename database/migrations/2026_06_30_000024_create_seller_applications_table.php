<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seller_applications', function (Blueprint $table) {
            $table->id();
            $table->string('token', 64)->unique()->comment('token สำหรับผู้สมัครติดตามสถานะ (ไม่ต้อง login)');

            // ข้อมูลผู้สมัคร
            $table->string('applicant_name')->comment('ชื่อ-นามสกุลผู้สมัคร');
            $table->string('applicant_phone', 30)->comment('เบอร์โทรผู้สมัคร');
            $table->string('applicant_email')->nullable()->comment('อีเมลผู้สมัคร (ไม่บังคับ)');
            $table->string('applicant_id_card', 20)->nullable()->comment('เลขบัตรประชาชน');

            // ข้อมูลกิจการ
            $table->string('business_name')->comment('ชื่อร้าน/กิจการ');
            $table->string('business_type', 100)->nullable()->comment('ประเภทสินค้า/บริการ');
            $table->string('district', 100)->nullable()->comment('อำเภอที่ตั้งกิจการ');
            $table->string('business_address')->nullable()->comment('ที่อยู่กิจการ');
            $table->text('products_description')->nullable()->comment('รายละเอียดสินค้า/บริการที่จะขาย');
            $table->text('note')->nullable()->comment('หมายเหตุเพิ่มเติมจากผู้สมัคร');
            $table->json('documents')->nullable()->comment('เอกสารแนบ (path[])');

            // กลุ่มที่ต้องการสังกัด
            $table->foreignId('requested_group_id')->nullable()->constrained('seller_groups')->nullOnDelete()
                ->comment('กลุ่มผู้ขายที่ต้องการสังกัด');

            // สถานะ
            $table->enum('status', [
                'pending',          // รอ admin พิจารณา
                'admin_approved',   // admin อนุมัติขั้น 1 — รอ superadmin
                'escalated',        // admin ไม่ตอบใน 1 ชม → escalate ไป superadmin โดยตรง
                'approved',         // superadmin อนุมัติ (บัญชีสร้างแล้ว)
                'rejected',         // ปฏิเสธ (ขั้นใดก็ได้)
            ])->default('pending')->comment('สถานะคำขอ');

            // Admin review (ขั้น 1)
            $table->foreignId('admin_id')->nullable()->constrained('users')->nullOnDelete()
                ->comment('admin ที่พิจารณาขั้น 1');
            $table->text('admin_note')->nullable()->comment('หมายเหตุ admin');
            $table->timestamp('admin_reviewed_at')->nullable()->comment('เวลา admin พิจารณา');
            $table->timestamp('escalated_at')->nullable()->comment('เวลา auto-escalate ไป superadmin');

            // Super Admin review (ขั้น 2 / ขั้นเดียว)
            $table->foreignId('superadmin_id')->nullable()->constrained('users')->nullOnDelete()
                ->comment('superadmin ที่อนุมัติขั้นสุดท้าย');
            $table->text('superadmin_note')->nullable()->comment('หมายเหตุ superadmin');
            $table->timestamp('superadmin_reviewed_at')->nullable();

            // ผลการอนุมัติ — กลุ่มที่กำหนด + user ที่สร้าง
            $table->foreignId('assigned_group_id')->nullable()->constrained('seller_groups')->nullOnDelete()
                ->comment('กลุ่มที่ superadmin กำหนดให้ (อาจต่างจาก requested)');
            $table->foreignId('created_user_id')->nullable()->constrained('users')->nullOnDelete()
                ->comment('user account ที่สร้างให้ผู้ได้รับอนุมัติ');

            $table->timestamps();
            $table->softDeletes();

            $table->index(['status', 'created_at'], 'sa_status_created_idx');
            $table->index('token');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seller_applications');
    }
};
