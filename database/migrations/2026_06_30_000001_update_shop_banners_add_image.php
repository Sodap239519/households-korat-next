<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shop_banners', function (Blueprint $table) {
            $table->string('image_path')->nullable()->after('tag')->comment('รูปภาพ hero (path ใน storage/public)');
            $table->string('title')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('shop_banners', function (Blueprint $table) {
            $table->dropColumn('image_path');
            $table->string('title')->nullable(false)->change();
        });
    }
};
