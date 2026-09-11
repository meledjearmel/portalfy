<?php

namespace Database\Seeders;

use App\Models\Package;
use Illuminate\Database\Seeder;

class PackageSeeder extends Seeder
{
    /**
     * Forfaits de démonstration.
     */
    public function run(): void
    {
        $packages = [
            ['name' => '1 heure', 'price' => 200, 'duration_minutes' => 60, 'max_speed_mbps' => 2, 'max_devices' => 1, 'sort_order' => 1],
            ['name' => '1 jour', 'price' => 500, 'duration_minutes' => 1440, 'max_speed_mbps' => 5, 'max_devices' => 2, 'sort_order' => 2, 'is_popular' => true],
            ['name' => '1 semaine', 'price' => 2000, 'duration_minutes' => 10080, 'max_speed_mbps' => 10, 'max_devices' => 3, 'sort_order' => 3],
            ['name' => '1 mois', 'price' => 5000, 'duration_minutes' => 43200, 'max_speed_mbps' => 20, 'max_devices' => 4, 'sort_order' => 4],
        ];

        foreach ($packages as $package) {
            Package::query()->updateOrCreate(
                ['name' => $package['name']],
                $package + ['is_active' => true, 'is_popular' => false]
            );
        }
    }
}
