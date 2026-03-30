<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mushroom_followups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('allocation_id')->constrained('mushroom_allocations')->restrictOnDelete();
            $table->unsignedTinyInteger('followup_round')->default(1)->comment('รอบติดตาม');
            $table->date('followup_date')->nullable()->comment('วันที่ติดตาม');
            $table->decimal('harvest_kg', 10, 3)->nullable()->default(0)->comment('ผลผลิต (กก.)');
            $table->decimal('sold_kg', 10, 3)->nullable()->default(0)->comment('ขายได้ (กก.)');
            $table->decimal('price_per_kg', 8, 2)->nullable()->comment('ราคา/กก.');
            $table->decimal('revenue', 12, 2)->nullable()->comment('รายได้ (คำนวณอัตโนมัติถ้า null)');
            $table->string('sale_channel')->nullable()->comment('direct|online|enterprise|market');
            $table->string('sale_place')->nullable()->comment('ชื่อตลาด/ร้าน/แพลตฟอร์ม');
            $table->boolean('enterprise_member')->default(false)->comment('สมาชิกวิสาหกิจ');
            $table->string('enterprise_name')->nullable()->comment('ชื่อวิสาหกิจ');
            $table->text('note')->nullable()->comment('หมายเหตุ');
            $table->timestamps();
            $table->unique(['allocation_id', 'followup_round']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mushroom_followups');
    }
};
