<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shop_banners', function (Blueprint $table) {
            $table->string('cta_label')->nullable()->default(null)->change();
            $table->string('cta_icon')->nullable()->default(null)->change();
            $table->enum('link_type', ['product', 'category', 'group', 'url', 'none'])->default('none')->change();
        });
    }

    public function down(): void
    {
        Schema::table('shop_banners', function (Blueprint $table) {
            $table->string('cta_label')->default('ดูสินค้า')->change();
            $table->string('cta_icon')->default('fi fi-rr-shopping-cart')->change();
            $table->enum('link_type', ['product', 'category', 'group', 'url'])->default('url')->change();
        });
    }
};
