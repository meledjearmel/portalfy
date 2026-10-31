<?php

namespace Database\Factories;

use App\Models\Package;
use App\Models\VoucherBatch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<VoucherBatch>
 */
class VoucherBatchFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'package_id' => Package::factory(),
            'quantity' => fake()->numberBetween(1, 50),
        ];
    }
}
