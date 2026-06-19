<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seller_groups', function (Blueprint $table) {
            // ข้อมูลบัญชีรับเงินของกลุ่ม (แสดงในหน้าโอนเงิน + ใช้ตรวจสลิป)
            $table->string('bank_name')->nullable()->after('contact_address')->comment('ชื่อธนาคาร');
            $table->string('bank_account_no', 30)->nullable()->after('bank_name')->comment('เลขที่บัญชี');
            $table->string('bank_account_name')->nullable()->after('bank_account_no')->comment('ชื่อบัญชี (ใช้เทียบกับชื่อผู้รับในสลิป)');
            $table->string('promptpay_id', 30)->nullable()->after('bank_account_name')->comment('พร้อมเพย์ (เบอร์/เลขบัตร ปชช.)');
        });
    }

    public function down(): void
    {
        Schema::table('seller_groups', function (Blueprint $table) {
            $table->dropColumn(['bank_name', 'bank_account_no', 'bank_account_name', 'promptpay_id']);
        });
    }
};
