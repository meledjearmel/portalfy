<?php

namespace Database\Seeders;

use App\Enums\CustomerStatus;
use App\Models\Customer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class CustomerSeeder extends Seeder
{
    /**
     * Compte client de démonstration (guard customer).
     */
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        Customer::query()->updateOrCreate(
            ['email' => dev_customer_mail()],
            [
                'phone' => '0700000001',
                'password' => Hash::make(dev_password()),
                'email_verified_at' => now(),
                'status' => CustomerStatus::Active,
            ]
        );
    }
}
