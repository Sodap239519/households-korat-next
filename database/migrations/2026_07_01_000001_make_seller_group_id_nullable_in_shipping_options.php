<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seller_shipping_options', function (Blueprint $table) {
            $table->unsignedBigInteger('seller_group_id')->nullable()->change();
        });

        // เปลี่ยนตัวเลือกที่มีอยู่ให้เป็น global (seller_group_id = null)
        DB::table('seller_shipping_options')->update(['seller_group_id' => null]);
    }

    public function down(): void
    {
        Schema::table('seller_shipping_options', function (Blueprint $table) {
            $table->unsignedBigInteger('seller_group_id')->nullable(false)->change();
        });
    }
};
