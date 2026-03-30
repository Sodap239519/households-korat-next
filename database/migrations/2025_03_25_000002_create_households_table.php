<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // 1) ถ้ายังไม่มีตารางเลย ให้ create ตามสคีม่าใหม่
        if (! Schema::hasTable('households')) {
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

            return;
        }

        // 2) ถ้ามีตารางอยู่แล้ว (legacy) ให้ sync เฉพาะคอลัมน์ที่ขาด
        Schema::table('households', function (Blueprint $table) {
            // NOTE: ห้ามแก้ type/rename/drop ในขั้นนี้ เพื่อไม่กระทบข้อมูลเดิม

            if (! Schema::hasColumn('households', 'household_code')) {
                $table->string('household_code')->nullable()->comment('รหัสครัวเรือน');
            }

            if (! Schema::hasColumn('households', 'prefix')) {
                $table->string('prefix')->nullable()->comment('คำนำหน้า');
            }

            if (! Schema::hasColumn('households', 'first_name')) {
                $table->string('first_name')->nullable()->comment('ชื่อ');
            }

            if (! Schema::hasColumn('households', 'last_name')) {
                $table->string('last_name')->nullable()->comment('นามสกุล');
            }

            if (! Schema::hasColumn('households', 'id_card')) {
                $table->string('id_card')->nullable()->comment('บัตรประชาชน');
            }

            if (! Schema::hasColumn('households', 'phone')) {
                $table->string('phone')->nullable()->comment('เบอร์โทร');
            }

            if (! Schema::hasColumn('households', 'village')) {
                $table->string('village')->nullable()->comment('หมู่บ้าน');
            }

            if (! Schema::hasColumn('households', 'sub_district')) {
                $table->string('sub_district')->nullable()->comment('ตำบล');
            }

            if (! Schema::hasColumn('households', 'district')) {
                $table->string('district')->nullable()->comment('อำเภอ');
            }

            if (! Schema::hasColumn('households', 'province')) {
                $table->string('province')->nullable()->default('นครราชสีมา')->comment('จังหวัด');
            }

            if (! Schema::hasColumn('households', 'postal_code')) {
                $table->string('postal_code')->nullable()->comment('รหัสไปรษณีย์');
            }

            if (! Schema::hasColumn('households', 'is_active')) {
                $table->boolean('is_active')->default(true)->comment('สถานะ');
            }

            if (! Schema::hasColumn('households', 'note')) {
                $table->text('note')->nullable()->comment('หมายเหตุ');
            }

            if (! Schema::hasColumn('households', 'created_at')) {
                $table->timestamp('created_at')->nullable();
            }

            if (! Schema::hasColumn('households', 'updated_at')) {
                $table->timestamp('updated_at')->nullable();
            }

            if (! Schema::hasColumn('households', 'deleted_at')) {
                $table->timestamp('deleted_at')->nullable();
            }
        });

        // 3) Sync indexes/unique แบบระวัง (เฉพาะถ้าไม่มีจริง)
        // ใช้ SHOW INDEX เพื่อเลี่ยงพึ่ง doctrine/dbal
        $existingIndexes = collect(DB::select('SHOW INDEX FROM `households`'))
            ->pluck('Key_name')
            ->unique()
            ->values()
            ->all();

        // unique household_code (ใน legacy dump มีอยู่แล้วชื่อ households_household_code_unique)
        if (! in_array('households_household_code_unique', $existingIndexes, true)) {
            // ถ้ามีข้อมูลซ้ำจะ error ตรงนี้ ต้องเคลียร์ข้อมูลก่อน
            try {
                Schema::table('households', function (Blueprint $table) {
                    $table->unique('household_code', 'households_household_code_unique');
                });
            } catch (\Throwable $e) {
                // เลี่ยงให้ migrate ล้มเพราะข้อมูลเดิมซ้ำ
                // ถ้าต้องการ strict ให้ลบ try/catch แล้วแก้ข้อมูลซ้ำใน DB
            }
        }

        // unique id_card (ใน dump ปัจจุบันไม่มี index นี้ แต่ของคุณอยากให้มี)
        // ทำแบบ optional: ถ้าคุณต้องการบังคับจริง ค่อยเอา try/catch ออก
        if (Schema::hasColumn('households', 'id_card') && ! in_array('households_id_card_unique', $existingIndexes, true)) {
            try {
                Schema::table('households', function (Blueprint $table) {
                    $table->unique('id_card', 'households_id_card_unique');
                });
            } catch (\Throwable $e) {
                // ข้ามถ้าข้อมูลเดิมมี id_card ซ้ำ/NULL เยอะจนชนข้อจำกัด (MariaDB อนุญาต NULL หลายค่าได้ แต่ค่าซ้ำที่ไม่ใช่ NULL จะชน)
            }
        }
    }

    public function down(): void
    {
        // สำคัญ: ในกรณี legacy DB เราไม่อยาก drop ตารางทิ้ง
        // ให้ drop เฉพาะตอนที่ตารางถูกสร้างโดย migration นี้จริง ๆ เท่านั้น
        // แต่เรารู้ไม่ได้แน่ชัด -> ปลอดภัยสุดคือไม่ทำอะไร
        // Schema::dropIfExists('households');
    }
};