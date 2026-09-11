<?php

namespace Database\Factories;

use App\Enums\CredentialMode;
use App\Models\WifiZoneSetting;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WifiZoneSetting>
 */
class WifiZoneSettingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'RapidNet Wifi Zone',
            'slogan' => fake()->sentence(4),
            'description' => fake()->sentence(),
            'color_primary' => '#0F9D8C',
            'color_secondary' => '#0B2B26',
            'color_accent' => '#0F9D8C',
            'phone' => fake()->phoneNumber(),
            'whatsapp' => fake()->phoneNumber(),
            'email' => fake()->companyEmail(),
            'address' => fake()->address(),
            'opening_hours' => '24h/24, 7j/7',
            'social_links' => [],
            'terms' => fake()->paragraph(),
            'privacy_policy' => fake()->paragraph(),
            'credential_mode' => CredentialMode::Unique,
        ];
    }
}
