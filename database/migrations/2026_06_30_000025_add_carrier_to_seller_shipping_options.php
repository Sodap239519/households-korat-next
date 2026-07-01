<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seller_shipping_options', function (Blueprint $table) {
            $table->string('carrier', 60)->nullable()->after('name')
                ->comment('บริษัทขนส่ง เช่น Kerry, Flash, ไปรษณีย์ไทย');
        });
    }

    public function down(): void
    {
        Schema::table('seller_shipping_options', function (Blueprint $table) {
            $table->dropColumn('carrier');
        });
    }
};
