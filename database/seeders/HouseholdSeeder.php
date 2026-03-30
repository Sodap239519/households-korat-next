<?php

namespace Database\Seeders;

use App\Models\Household;
use Illuminate\Database\Seeder;

class HouseholdSeeder extends Seeder
{
    public function run(): void
    {
        $households = [
            ['household_code' => 'HH-001', 'prefix' => 'นาย', 'first_name' => 'สมชาย', 'last_name' => 'ใจดี', 'village' => 'บ้านเก่า', 'sub_district' => 'โพธิ์กลาง', 'district' => 'เมืองนครราชสีมา'],
            ['household_code' => 'HH-002', 'prefix' => 'นาง', 'first_name' => 'สมหญิง', 'last_name' => 'รักดี', 'village' => 'บ้านใหม่', 'sub_district' => 'ตลาด', 'district' => 'เมืองนครราชสีมา'],
            ['household_code' => 'HH-003', 'prefix' => 'นาย', 'first_name' => 'ประสิทธิ์', 'last_name' => 'มีสุข', 'village' => 'บ้านดอน', 'sub_district' => 'บ้านโพธิ์', 'district' => 'โชคชัย'],
            ['household_code' => 'HH-004', 'prefix' => 'นางสาว', 'first_name' => 'วิมล', 'last_name' => 'สวัสดี', 'village' => 'บ้านท่า', 'sub_district' => 'ท่าเยี่ยม', 'district' => 'โชคชัย'],
            ['household_code' => 'HH-005', 'prefix' => 'นาย', 'first_name' => 'ชาติ', 'last_name' => 'ศรีทอง', 'village' => 'บ้านป่า', 'sub_district' => 'กฤษณา', 'district' => 'สีคิ้ว'],
        ];

        foreach ($households as $data) {
            Household::firstOrCreate(
                ['household_code' => $data['household_code']],
                array_merge($data, ['province' => 'นครราชสีมา', 'is_active' => true])
            );
        }
    }
}
