<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_categories', function (Blueprint $table) {
            // key ที่ map กับ business_categories ใน seller_applications
            // เช่น vegetable_fruit, mushroom, handicraft — null = ไม่จำกัดประเภท (ใช้ได้ทุกร้าน)
            $table->string('category_key', 50)->nullable()->after('code')
                ->comment('map กับ business_categories key เช่น vegetable_fruit, mushroom — null = ไม่จำกัด');
        });
    }

    public function down(): void
    {
        Schema::table('product_categories', function (Blueprint $table) {
            $table->dropColumn('category_key');
        });
    }
};
