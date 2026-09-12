<?php

namespace App\Listeners;

use App\Enums\OrderStatus;
use App\Models\Order;
use GeniusPay\Laravel\Events\PaymentFailed;
use Illuminate\Support\Facades\Log;

class MarkOrderAsFailed
{
    /**
     * Handle the event.
     */
    public function handle(PaymentFailed $event): void
    {
        $order = Order::query()->where('payment_reference', $event->getReference())->first();

        if (! $order) {
            Log::warning('GeniusPay: paiement échoué pour une commande introuvable', [
                'reference' => $event->getReference(),
            ]);

            return;
        }

        if ($order->status === OrderStatus::Paid) {
            return;
        }

        $order->update(['status' => OrderStatus::Failed]);
    }
}
