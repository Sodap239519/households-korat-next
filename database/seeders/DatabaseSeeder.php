<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            SellerGroupSeeder::class,
            UserSeeder::class,
            HouseholdSeeder::class,
            MushroomQuotaDistrictSeeder::class,
            MarketDemoSeeder::class,
        ]);
    }
}
