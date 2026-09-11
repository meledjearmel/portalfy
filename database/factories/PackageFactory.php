<?php

namespace Database\Factories;

use App\Models\Package;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Package>
 */
class PackageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['1 heure', '1 jour', '1 semaine', '1 mois']),
            'price' => fake()->randomElement([200, 500, 2000, 5000]),
            'duration_minutes' => fake()->randomElement([60, 1440, 10080, 43200]),
            'max_speed_mbps' => fake()->randomElement([2, 5, 10, 20]),
            'max_devices' => fake()->numberBetween(1, 4),
            'short_description' => fake()->sentence(6),
            'is_popular' => false,
            'is_active' => true,
            'sort_order' => 0,
        ];
    }

    /**
     * Indicate that the package is highlighted as "Populaire".
     */
    public function popular(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_popular' => true,
        ]);
    }

    /**
     * Indicate that the package is inactive.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }
}
