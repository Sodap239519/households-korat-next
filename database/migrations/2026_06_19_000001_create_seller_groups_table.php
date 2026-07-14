<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seller_groups', function (Blueprint $table) {
            $table->id();
            $table->string('name')->comment('ชื่อกลุ่มผู้ขาย');
            $table->string('slug')->unique()->comment('slug สำหรับ URL หน้าร้าน');
            $table->text('description')->nullable()->comment('รายละเอียดกลุ่ม');
            $table->string('contact_phone', 30)->nullable()->comment('เบอร์ติดต่อ');
            $table->string('contact_address')->nullable()->comment('ที่อยู่/ที่ตั้งกลุ่ม');
            $table->string('logo_path')->nullable()->comment('โลโก้กลุ่ม (path ใน storage)');
            $table->json('districts')->nullable()->comment('รายชื่ออำเภอที่กลุ่มดูแล (อ้างอิงจาก households)');
            $table->boolean('is_active')->default(true)->comment('เปิดใช้งานกลุ่มหรือไม่');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seller_groups');
    }
};
