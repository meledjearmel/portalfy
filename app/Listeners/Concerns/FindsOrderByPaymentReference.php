<?php

namespace App\Listeners\Concerns;

use App\Models\Order;
use Illuminate\Support\Facades\Log;

trait FindsOrderByPaymentReference
{
    protected function findOrder(?string $reference): ?Order
    {
        $order = Order::query()->where('payment_reference', $reference)->first();

        if (! $order) {
            Log::warning('GeniusPay: événement de paiement reçu pour une commande introuvable', [
                'reference' => $reference,
            ]);
        }

        return $order;
    }
}
