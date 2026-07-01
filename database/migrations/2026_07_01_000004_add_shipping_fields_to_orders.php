<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // shipping_method อาจมีแล้วจาก migration เก่า ให้ข้ามถ้ามีแล้ว
            if (!Schema::hasColumn('orders', 'shipping_method')) {
                $table->string('shipping_method')->nullable()->after('shipping_fee')
                    ->comment('ชื่อบริการจัดส่งที่ลูกค้าเลือก (snapshot จาก seller_shipping_options.name)');
            }
            $table->string('shipping_carrier', 60)->nullable()->after('shipping_method')
                ->comment('บริษัทขนส่ง (snapshot จาก seller_shipping_options.carrier) ถ้า null = จัดส่งเอง');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn('shipping_carrier');
        });
    }
};
