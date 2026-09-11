<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Compte admin de démonstration (guard web).
     */
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            return;
        }

        User::query()->updateOrCreate(
            ['email' => dev_admin_mail()],
            [
                'name' => 'Armel Admin',
                'password' => Hash::make(dev_password()),
                'email_verified_at' => now(),
            ]
        );
    }
}
