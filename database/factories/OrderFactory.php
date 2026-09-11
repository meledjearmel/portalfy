<?php

namespace Database\Factories;

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Package;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'reference' => Str::upper(Str::random(10)),
            'package_id' => Package::factory(),
            'customer_id' => null,
            'phone' => fake()->numerify('07########'),
            'amount' => fake()->randomElement([200, 500, 2000, 5000]),
            'status' => OrderStatus::Pending,
            'payment_provider' => null,
            'payment_reference' => null,
        ];
    }

    /**
     * Indicate that the order has been paid.
     */
    public function paid(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrderStatus::Paid,
            'payment_provider' => 'geniuspay',
            'payment_reference' => Str::upper(Str::random(12)),
        ]);
    }

    /**
     * Indicate that the payment failed.
     */
    public function failed(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => OrderStatus::Failed,
        ]);
    }
}
