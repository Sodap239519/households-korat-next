<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // เจ้าของสินค้า — staff ที่สร้างสินค้า (null = สร้างโดยระบบ/admin)
            $table->foreignId('seller_user_id')
                  ->nullable()
                  ->after('seller_group_id')
                  ->constrained('users')
                  ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['seller_user_id']);
            $table->dropColumn('seller_user_id');
        });
    }
};
