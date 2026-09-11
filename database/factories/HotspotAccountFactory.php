<?php

namespace Database\Factories;

use App\Enums\HotspotAccountStatus;
use App\Models\HotspotAccount;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<HotspotAccount>
 */
class HotspotAccountFactory extends Factory
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
            'code' => Str::upper(Str::random(8)),
            'secret' => null,
            'status' => HotspotAccountStatus::Active,
            'activated_at' => now(),
            'expires_at' => now()->addDay(),
        ];
    }

    /**
     * Indicate that the access has expired.
     */
    public function expired(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => HotspotAccountStatus::Expired,
            'expires_at' => now()->subHour(),
        ]);
    }

    /**
     * Indicate that the access has been suspended.
     */
    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => HotspotAccountStatus::Suspended,
        ]);
    }
}
