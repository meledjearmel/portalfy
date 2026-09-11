<?php

namespace Database\Factories;

use App\Models\Invoice;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Invoice>
 */
class InvoiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory()->paid(),
            'number' => fake()->unique()->numberBetween(1, 1_000_000),
            'amount' => fake()->randomElement([200, 500, 2000, 5000]),
            'pdf_path' => null,
        ];
    }
}
