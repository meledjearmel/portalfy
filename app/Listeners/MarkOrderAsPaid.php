<?php

namespace App\Listeners;

use App\Enums\OrderStatus;
use App\Models\Invoice;
use App\Models\Order;
use GeniusPay\Laravel\Events\PaymentCompleted;
use Illuminate\Support\Facades\Log;

class MarkOrderAsPaid
{
    /**
     * Handle the event.
     */
    public function handle(PaymentCompleted $event): void
    {
        $order = Order::query()->where('payment_reference', $event->getReference())->first();

        if (! $order) {
            Log::warning('GeniusPay: paiement complété pour une commande introuvable', [
                'reference' => $event->getReference(),
            ]);

            return;
        }

        if ($order->status === OrderStatus::Paid) {
            return;
        }

        $order->update(['status' => OrderStatus::Paid]);

        if ($order->customer_id && ! $order->invoice) {
            Invoice::create([
                'order_id' => $order->id,
                'number' => (Invoice::query()->max('number') ?? 0) + 1,
                'amount' => $order->amount,
            ]);
        }

        // TODO étape 6 : ProvisionHotspotAccountAction (génération du code,
        // création du compte RouterOS selon credential_mode).
        // TODO étape 7 : diffuser un event Reverb sur le channel privé de
        // la commande pour afficher le code en temps réel.
    }
}
