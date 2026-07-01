<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seller_applications', function (Blueprint $table) {
            $table->foreignId('applicant_user_id')->nullable()->after('token')
                ->constrained('users')->nullOnDelete()
                ->comment('user ที่ยื่นคำขอ (เชื่อมกับ users ถ้า login ตอนสมัคร)');
        });
    }

    public function down(): void
    {
        Schema::table('seller_applications', function (Blueprint $table) {
            $table->dropForeign(['applicant_user_id']);
            $table->dropColumn('applicant_user_id');
        });
    }
};
