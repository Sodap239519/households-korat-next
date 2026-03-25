<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('households', function (Blueprint $table) {
            $table->id();
            $table->string('household_code')->unique()->comment('รหัสครัวเรือน');
            $table->string('prefix')->nullable()->comment('คำนำหน้า');
            $table->string('first_name')->comment('ชื่อ');
            $table->string('last_name')->comment('นามสกุล');
            $table->string('id_card')->nullable()->unique()->comment('บัตรประชาชน');
            $table->string('phone')->nullable()->comment('เบอร์โทร');
            $table->string('village')->nullable()->comment('หมู่บ้าน');
            $table->string('sub_district')->nullable()->comment('ตำบล');
            $table->string('district')->nullable()->comment('อำเภอ');
            $table->string('province')->nullable()->default('นครราชสีมา')->comment('จังหวัด');
            $table->string('postal_code')->nullable()->comment('รหัสไปรษณีย์');
            $table->boolean('is_active')->default(true)->comment('สถานะ');
            $table->text('note')->nullable()->comment('หมายเหตุ');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('households');
    }
};
