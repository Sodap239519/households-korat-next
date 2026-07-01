<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('product_categories', function (Blueprint $table) {
            $table->string('code', 20)->nullable()->after('name')
                ->comment('รหัสประเภท (A-Z, 1-20 ตัว) ใช้นำหน้า SKU เช่น MUSH, VEG, HERB');
        });
    }

    public function down(): void
    {
        Schema::table('product_categories', function (Blueprint $table) {
            $table->dropColumn('code');
        });
    }
};
