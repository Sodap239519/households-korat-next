<?php

namespace Database\Seeders;

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
                'name'     => 'ผู้ดูแลระบบ',
                'role'     => 'superadmin',
                'password' => Hash::make('password'),
            ]
        );

        User::firstOrCreate(
            ['email' => 'staff@households-korat.local'],
            [
                'name'     => 'เจ้าหน้าที่',
                'role'     => 'staff',
                'password' => Hash::make('password'),
            ]
        );
    }
}
