<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // รายการ ID บริการจัดส่งที่สินค้านี้รองรับ (null = ใช้ทุกบริการของกลุ่ม)
            $table->json('shipping_option_ids')->nullable()->after('flash_sale_end');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('shipping_option_ids');
        });
    }
};
