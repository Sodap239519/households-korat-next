<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('shipping_option_id')->nullable()->after('shipping_carrier')
                ->comment('ID ของ seller_shipping_options ที่ลูกค้าเลือกสำหรับออเดอร์นี้');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('shipping_option_id');
        });
    }
};
