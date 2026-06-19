<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seller_groups', function (Blueprint $table) {
            // ปลายทางแจ้งเตือน LINE ของกลุ่ม (groupId/userId สำหรับ push ผ่าน OA กลาง)
            $table->string('line_target_id')->nullable()->after('logo_path')
                ->comment('LINE target id (กลุ่มแชต/ผู้ใช้) สำหรับ push แจ้งเตือน');
            $table->boolean('line_notify_enabled')->default(true)->after('line_target_id')
                ->comment('เปิด/ปิดการแจ้งเตือน LINE ของกลุ่มนี้');
        });
    }

    public function down(): void
    {
        Schema::table('seller_groups', function (Blueprint $table) {
            $table->dropColumn(['line_target_id', 'line_notify_enabled']);
        });
    }
};
