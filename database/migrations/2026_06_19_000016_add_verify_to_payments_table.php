<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            // ผลการตรวจสลิปอัตโนมัติ (ถ้าเปิดใช้ผ่านบริการตรวจสลิป)
            $table->string('verify_status')->default('unchecked')->after('status')
                ->comment('unchecked|passed|failed|skipped — ผลตรวจสลิปอัตโนมัติ');
            $table->json('verify_detail')->nullable()->after('verify_status')
                ->comment('รายละเอียดผลตรวจ (จำนวนเงิน/ผู้รับ/เวลา/เหตุที่ไม่ผ่าน)');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn(['verify_status', 'verify_detail']);
        });
    }
};
