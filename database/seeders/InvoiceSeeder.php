<?php

namespace Database\Seeders;

use App\Enums\OrderStatus;
use App\Models\Invoice;
use App\Models\Order;
use Illuminate\Database\Seeder;

class InvoiceSeeder extends Seeder
{
    /**
     * Une facture par commande payée et rattachée à un client, sans en avoir déjà une.
     */
    public function run(): void
    {
        Order::query()
            ->where('status', OrderStatus::Paid)
            ->whereNotNull('customer_id')
            ->doesntHave('invoice')
            ->each(function (Order $order) {
                $number = (Invoice::query()->max('number') ?? 0) + 1;

                Invoice::query()->create([
                    'order_id' => $order->id,
                    'number' => $number,
                    'amount' => $order->amount,
                ]);
            });
    }
}
