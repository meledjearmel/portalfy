<?php

namespace Database\Factories;

use App\Models\RouterSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<RouterSetting>
 */
class RouterSettingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'host' => fake()->localIpv4(),
            'port' => 8728,
            'username' => 'admin',
            'password' => fake()->password(),
            'timeout' => 10,
            'use_ssl' => false,
        ];
    }
}
