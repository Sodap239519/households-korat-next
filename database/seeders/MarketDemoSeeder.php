<?php

namespace Database\Seeders;

use App\Models\Product;
use App\Models\ProductCategory;
use App\Models\SellerGroup;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class MarketDemoSeeder extends Seeder
{
    public function run(): void
    {
        // หมวดหมู่กลาง (ใช้ร่วมทุกกลุ่ม)
        $categories = [
            'ผลผลิตเห็ด'      => 'mushroom',
            'พืชผักปลอดภัย'    => 'vegetable',
            'ของแปรรูป'        => 'processed',
            'งานหัตถกรรม'      => 'handicraft',
        ];
        $catModels = [];
        $order = 0;
        foreach ($categories as $name => $slug) {
            $catModels[$slug] = ProductCategory::updateOrCreate(
                ['seller_group_id' => null, 'slug' => $slug],
                ['name' => $name, 'sort_order' => $order++, 'is_active' => true],
            );
        }

        // สินค้าตัวอย่าง 2-3 ชิ้น/กลุ่ม
        $samples = [
            ['เห็ดนางฟ้าสด', 'mushroom', 45, 'กก.', 'เห็ดนางฟ้าสดใหม่จากฟาร์มชุมชน'],
            ['เห็ดหอมแห้ง', 'processed', 120, 'ถุง', 'เห็ดหอมแห้งคัดพิเศษ บรรจุถุง 100 กรัม'],
            ['ผักสลัดอินทรีย์', 'vegetable', 35, 'ถุง', 'ผักสลัดปลอดสารพิษ สดจากแปลง'],
            ['น้ำพริกเห็ดสวรรค์', 'processed', 60, 'กระปุก', 'น้ำพริกเห็ดสูตรชุมชน หอม เผ็ดกลมกล่อม'],
            ['กระเป๋าสานพลาสติก', 'handicraft', 150, 'ใบ', 'งานสานมือจากกลุ่มแม่บ้าน'],
        ];

        SellerGroup::all()->each(function (SellerGroup $group, int $gi) use ($catModels, $samples) {
            // เลือกสินค้า 2-3 รายการต่อกลุ่ม วนจากตัวอย่าง
            $count = 2 + ($gi % 2); // 2 หรือ 3
            for ($i = 0; $i < $count; $i++) {
                $s = $samples[($gi + $i) % count($samples)];
                [$name, $catSlug, $price, $unit, $desc] = $s;
                $district = $group->districts[0] ?? null;
                $slug = Str::slug($group->slug . '-' . $name . '-' . ($i + 1), '-', null) ?: ($group->slug . '-p' . $i);

                Product::updateOrCreate(
                    ['slug' => $slug],
                    [
                        'seller_group_id'   => $group->id,
                        'category_id'       => $catModels[$catSlug]->id,
                        'name'              => $name . ' (' . $group->name . ')',
                        'sku'               => strtoupper($group->slug) . '-' . ($i + 1),
                        'short_description' => $desc,
                        'description'       => $desc . "\n\nสินค้าจาก{$group->name} อำเภอ{$district}",
                        'price'             => $price,
                        'sale_price'        => $i === 0 ? round($price * 0.9, 2) : null,
                        'stock_qty'         => 50,
                        'unit'              => $unit,
                        'district'          => $district,
                        'status'            => Product::STATUS_PUBLISHED,
                        'is_featured'       => $i === 0,
                    ],
                );
            }
        });
    }
}
