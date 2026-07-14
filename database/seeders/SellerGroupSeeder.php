<?php

namespace Database\Seeders;

use App\Models\SellerGroup;
use Illuminate\Database\Seeder;

class SellerGroupSeeder extends Seeder
{
    public function run(): void
    {
        // 5 กลุ่มผู้ขาย แบ่งตามโซนอำเภอ (อ้างอิงอำเภอที่มีในตาราง households)
        $groups = [
            [
                'name'        => 'กลุ่มตลาดโซนเมือง',
                'slug'        => 'zone-mueang',
                'description' => 'สินค้าชุมชนเขตเมืองนครราชสีมาและอำเภอใกล้เคียง',
                'districts'   => ['เมืองนครราชสีมา', 'เฉลิมพระเกียรติ', 'ขามทะเลสอ'],
            ],
            [
                'name'        => 'กลุ่มตลาดโซนใต้',
                'slug'        => 'zone-tai',
                'description' => 'สินค้าชุมชนโซนใต้ ปักธงชัย ครบุรี หนองบุญมาก',
                'districts'   => ['ปักธงชัย', 'ครบุรี', 'หนองบุญมาก'],
            ],
            [
                'name'        => 'กลุ่มตลาดโซนตะวันออก',
                'slug'        => 'zone-tawan-ok',
                'description' => 'สินค้าชุมชนโซนตะวันออก จักราช ห้วยแถลง ชุมพวง',
                'districts'   => ['จักราช', 'ห้วยแถลง', 'ชุมพวง'],
            ],
            [
                'name'        => 'กลุ่มตลาดโซนเหนือ',
                'slug'        => 'zone-nuea',
                'description' => 'สินค้าชุมชนโซนเหนือ โนนสูง',
                'districts'   => ['โนนสูง'],
            ],
            [
                'name'        => 'กลุ่มตลาดโซนตะวันตก',
                'slug'        => 'zone-tawan-tok',
                'description' => 'สินค้าชุมชนโซนตะวันตก สีคิ้ว ด่านขุนทด',
                'districts'   => ['สีคิ้ว', 'ด่านขุนทด'],
            ],
        ];

        foreach ($groups as $g) {
            SellerGroup::updateOrCreate(
                ['slug' => $g['slug']],
                array_merge($g, ['is_active' => true]),
            );
        }
    }
}
