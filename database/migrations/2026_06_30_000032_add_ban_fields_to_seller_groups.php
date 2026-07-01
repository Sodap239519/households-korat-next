<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seller_groups', function (Blueprint $table) {
            // สถานะร้าน: active=ปกติ, suspended=ระงับชั่วคราว, banned=แบนถาวร
            $table->string('shop_status', 20)->default('active')->after('is_active');
            $table->timestamp('suspended_until')->nullable()->after('shop_status');
            $table->text('ban_reason')->nullable()->after('suspended_until');
            $table->unsignedBigInteger('banned_by')->nullable()->after('ban_reason');
            $table->timestamp('banned_at')->nullable()->after('banned_by');
        });
    }

    public function down(): void
    {
        Schema::table('seller_groups', function (Blueprint $table) {
            $table->dropColumn(['shop_status', 'suspended_until', 'ban_reason', 'banned_by', 'banned_at']);
        });
    }
};
