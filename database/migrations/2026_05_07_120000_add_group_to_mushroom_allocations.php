<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mushroom_allocations', function (Blueprint $table) {
            // Allocations sharing the same group_code belong to the same group.
            // Null = บันทึกแบบรายเดี่ยว (เดิม)
            $table->string('group_code', 36)->nullable()->after('household_id')
                ->comment('UUID แบ่งกลุ่ม — null = รายเดี่ยว');
            $table->string('group_label', 120)->nullable()->after('group_code')
                ->comment('ชื่อกลุ่ม เช่น "กลุ่มเพาะเห็ดบ้านนาดี"');
            $table->index('group_code');
        });
    }

    public function down(): void
    {
        Schema::table('mushroom_allocations', function (Blueprint $table) {
            $table->dropIndex(['group_code']);
            $table->dropColumn(['group_code', 'group_label']);
        });
    }
};
