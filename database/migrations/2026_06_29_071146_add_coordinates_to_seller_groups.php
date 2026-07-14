<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('seller_groups', function (Blueprint $table) {
            $table->decimal('lat', 10, 7)->nullable()->after('districts')->comment('ละติจูดจุดศูนย์กลางพื้นที่ให้บริการ');
            $table->decimal('lng', 10, 7)->nullable()->after('lat')->comment('ลองจิจูดจุดศูนย์กลางพื้นที่ให้บริการ');
            $table->string('map_label')->nullable()->after('lng')->comment('ป้ายชื่อบนแผนที่ (ถ้าไม่กำหนดใช้ name แทน)');
        });
    }

    public function down(): void
    {
        Schema::table('seller_groups', function (Blueprint $table) {
            $table->dropColumn(['lat', 'lng', 'map_label']);
        });
    }
};
