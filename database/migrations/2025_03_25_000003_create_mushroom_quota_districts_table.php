<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mushroom_quota_districts', function (Blueprint $table) {
            $table->id();
            $table->string('district')->comment('ชื่ออำเภอ');
            $table->string('province')->default('นครราชสีมา')->comment('จังหวัด');
            $table->unsignedSmallInteger('year')->comment('ปี พ.ศ.');
            $table->unsignedTinyInteger('round')->comment('รอบ (1,2,3...)');
            $table->unsignedInteger('quota_bags')->comment('โควต้าจำนวนถุง');
            $table->boolean('is_active')->default(true)->comment('สถานะ');
            $table->text('note')->nullable()->comment('หมายเหตุ');
            $table->timestamps();
            $table->unique(['district', 'year', 'round']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mushroom_quota_districts');
    }
};
