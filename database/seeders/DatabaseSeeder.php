<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        if (app()->environment('local')) {
            $this->call([
                WifiZoneSettingSeeder::class,
                PackageSeeder::class,
                UserSeeder::class,
                CustomerSeeder::class,
                OrderSeeder::class,
                HotspotAccountSeeder::class,
                InvoiceSeeder::class,
            ]);
        }
    }
}
