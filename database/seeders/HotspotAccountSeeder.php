<?php

namespace Database\Seeders;

use App\Enums\OrderStatus;
use App\Models\HotspotAccount;
use App\Models\Order;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class HotspotAccountSeeder extends Seeder
{
    /**
     * Un accès Hotspot par commande payée, pour les commandes qui n'en ont pas encore.
     */
    public function run(): void
    {
        Order::query()
            ->where('status', OrderStatus::Paid)
            ->doesntHave('hotspotAccount')
            ->each(function (Order $order) {
                HotspotAccount::factory()->create([
                    'order_id' => $order->id,
                    'code' => Str::upper(Str::random(8)),
                ]);
            });
    }
}
