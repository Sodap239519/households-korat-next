<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('flash_sale_events', function (Blueprint $table) {
            $table->id();
            $table->string('title', 100);                                          // ชื่อ event
            $table->text('description')->nullable();                               // รายละเอียด
            $table->dateTime('starts_at');                                         // เริ่มต้น
            $table->dateTime('ends_at');                                           // สิ้นสุด
            $table->boolean('is_active')->default(true);                           // เปิด/ปิด
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete(); // ผู้สร้าง
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('flash_sale_events');
    }
};
