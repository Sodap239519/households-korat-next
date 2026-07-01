<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('seller_shipping_options', function (Blueprint $table) {
            $table->json('allowed_payment_methods')->nullable()->after('sort_order')
                ->comment('วิธีชำระเงินที่บริการนี้รองรับ: ["online","cod"]');
        });
    }

    public function down(): void
    {
        Schema::table('seller_shipping_options', function (Blueprint $table) {
            $table->dropColumn('allowed_payment_methods');
        });
    }
};
