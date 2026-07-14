<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shop_banners', function (Blueprint $table) {
            $table->string('aspect_ratio', 10)->default('3/1')->after('is_active')
                ->comment('อัตราส่วนภาพของ Hero section เช่น 3/1, 16/9, 2/1, 4/1');
        });
    }

    public function down(): void
    {
        Schema::table('shop_banners', function (Blueprint $table) {
            $table->dropColumn('aspect_ratio');
        });
    }
};
