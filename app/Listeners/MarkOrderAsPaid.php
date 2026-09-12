<?php

namespace App\Listeners;

use App\Enums\OrderStatus;
use App\Listeners\Concerns\FindsOrderByPaymentReference;
use App\Models\Invoice;
use GeniusPay\Laravel\Events\PaymentCompleted;
use Illuminate\Support\Facades\Cache;

class MarkOrderAsPaid
{
    use FindsOrderByPaymentReference;

    /**
     * Handle the event.
     *
     * Le provisioning RouterOS (génération du code d'accès) et la diffusion
     * temps réel Reverb sont branchés séparément par leurs propres listeners
     * une fois ces étapes construites.
     */
    public function handle(PaymentCompleted $event): void
    {
        $order = $this->findOrder($event->getReference());

        if (! $order || $order->status === OrderStatus::Paid) {
            return;
        }

        $order->update(['status' => OrderStatus::Paid]);

        if ($order->customer_id && ! $order->invoice) {
            Cache::lock('invoice-number-generation', 10)->block(5, function () use ($order) {
                Invoice::create([
                    'order_id' => $order->id,
                    'number' => (Invoice::query()->max('number') ?? 0) + 1,
                    'amount' => $order->amount,
                ]);
            });
        }
    }
}
