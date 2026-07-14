<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            // ร้านค้าย่อย (seller_user_id) ที่ลูกค้าเริ่มคุยด้วย (nullable = คุยกับทั้งโซน)
            $table->foreignId('seller_user_id')->nullable()->after('seller_group_id')
                ->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('conversations', function (Blueprint $table) {
            $table->dropForeign(['seller_user_id']);
            $table->dropColumn('seller_user_id');
        });
    }
};
