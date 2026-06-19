<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // กลุ่มผู้ขายที่ผู้ใช้สังกัด (เฉพาะเจ้าหน้าที่ที่ดูแลตลาด) — null = ไม่สังกัดกลุ่ม
            // role ค่าใหม่ 'customer' ใช้คอลัมน์ role เดิม (string) ไม่ต้องแก้ schema
            $table->foreignId('seller_group_id')->nullable()->after('assigned_districts')
                ->constrained('seller_groups')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('seller_group_id');
        });
    }
};
