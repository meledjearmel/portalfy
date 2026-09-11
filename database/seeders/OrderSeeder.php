<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Package;
use Illuminate\Database\Seeder;

class OrderSeeder extends Seeder
{
    /**
     * Commandes de démonstration : anonymes et liées au client de démo,
     * dans différents états pour couvrir les écrans admin/commandes.
     */
    public function run(): void
    {
        $package = Package::query()->first();
        $customer = Customer::query()->first();

        if (! $package) {
            return;
        }

        Order::factory()->create([
            'package_id' => $package->id,
            'phone' => '0700000099',
        ]);

        Order::factory()->paid()->create([
            'package_id' => $package->id,
            'phone' => '0700000098',
        ]);

        Order::factory()->failed()->create([
            'package_id' => $package->id,
            'phone' => '0700000097',
        ]);

        if ($customer) {
            Order::factory()->paid()->create([
                'package_id' => $package->id,
                'customer_id' => $customer->id,
                'phone' => $customer->phone,
            ]);
        }
    }
}
