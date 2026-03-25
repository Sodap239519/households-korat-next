<?php

namespace Database\Seeders;

use App\Models\MushroomQuotaDistrict;
use Illuminate\Database\Seeder;

class MushroomQuotaDistrictSeeder extends Seeder
{
    public function run(): void
    {
        $year = (int) date('Y') + 543; // ปี พ.ศ. ปัจจุบัน

        $quotas = [
            ['district' => 'เมืองนครราชสีมา', 'round' => 1, 'quota_bags' => 500],
            ['district' => 'เมืองนครราชสีมา', 'round' => 2, 'quota_bags' => 400],
            ['district' => 'โชคชัย',           'round' => 1, 'quota_bags' => 300],
            ['district' => 'โชคชัย',           'round' => 2, 'quota_bags' => 250],
            ['district' => 'สีคิ้ว',            'round' => 1, 'quota_bags' => 200],
            ['district' => 'ปักธงชัย',          'round' => 1, 'quota_bags' => 350],
            ['district' => 'บัวใหญ่',           'round' => 1, 'quota_bags' => 150],
        ];

        foreach ($quotas as $data) {
            MushroomQuotaDistrict::firstOrCreate(
                [
                    'district' => $data['district'],
                    'year'     => $year,
                    'round'    => $data['round'],
                ],
                [
                    'province'   => 'นครราชสีมา',
                    'quota_bags' => $data['quota_bags'],
                    'is_active'  => true,
                ]
            );
        }
    }
}
