<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seller_applications', function (Blueprint $table) {
            $table->string('sub_district', 100)->nullable()->after('district')->comment('ตำบล/แขวงที่ตั้งกิจการ');
            $table->decimal('lat', 10, 6)->nullable()->after('business_address')->comment('ละติจูดพิกัดร้าน');
            $table->decimal('lng', 10, 6)->nullable()->after('lat')->comment('ลองจิจูดพิกัดร้าน');
            $table->json('business_categories')->nullable()->after('business_type')->comment('ประเภทสินค้า/บริการ (array key เช่น vegetable_fruit)');
        });
    }

    public function down(): void
    {
        Schema::table('seller_applications', function (Blueprint $table) {
            $table->dropColumn(['sub_district', 'lat', 'lng', 'business_categories']);
        });
    }
};
