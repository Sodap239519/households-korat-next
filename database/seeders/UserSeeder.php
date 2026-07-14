<?php

namespace Database\Seeders;

use App\Models\SellerGroup;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@households-korat.local'],
            [
                'name'        => 'ผู้ดูแลระบบ',
                'role'        => 'superadmin',
                'password'    => Hash::make('password'),
                'is_approved' => true,
            ]
        );

        User::firstOrCreate(
            ['email' => 'staff@households-korat.local'],
            [
                'name'        => 'เจ้าหน้าที่',
                'role'        => 'staff',
                'password'    => Hash::make('password'),
                'is_approved' => true,
            ]
        );

        // เจ้าหน้าที่ประจำกลุ่มตลาด 1 คน/กลุ่ม (สังกัด seller_group)
        SellerGroup::all()->each(function (SellerGroup $group, int $i) {
            User::firstOrCreate(
                ['email' => "seller{$group->slug}@households-korat.local"],
                [
                    'name'            => 'เจ้าหน้าที่ ' . $group->name,
                    'role'            => User::ROLE_AREA_STAFF,
                    'password'        => Hash::make('password'),
                    'is_approved'     => true,
                    'seller_group_id' => $group->id,
                ]
            );
        });

        // ลูกค้าตัวอย่างสำหรับทดสอบหน้าร้าน
        User::firstOrCreate(
            ['email' => 'customer@example.com'],
            [
                'name'        => 'ลูกค้าทดสอบ',
                'role'        => User::ROLE_CUSTOMER,
                'password'    => Hash::make('password'),
                'is_approved' => true,
            ]
        );
    }
}
