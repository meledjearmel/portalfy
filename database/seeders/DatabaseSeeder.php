<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     *
     * Hors local, seules les données essentielles au fonctionnement du portail
     * sont semées (paramètres de la zone, forfaits de départ), sans jamais
     * écraser ce que l'admin a déjà configuré.
     */
    public function run(): void
    {
        $this->call([
            WifiZoneSettingSeeder::class,
            PackageSeeder::class,
        ]);

        if (app()->environment('local')) {
            $this->call([
                UserSeeder::class,
                CustomerSeeder::class,
                OrderSeeder::class,
                HotspotAccountSeeder::class,
                InvoiceSeeder::class,
            ]);
        }
    }
}
