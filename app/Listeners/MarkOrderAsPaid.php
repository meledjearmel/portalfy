<?php

namespace App\Listeners;

use App\Actions\ProvisionHotspotAccountAction;
use App\Enums\OrderStatus;
use App\Listeners\Concerns\FindsOrderByPaymentReference;
use App\Models\Invoice;
use App\Models\Order;
use GeniusPay\Laravel\Events\PaymentCompleted;
use Illuminate\Contracts\Cache\LockTimeoutException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class MarkOrderAsPaid
{
    use FindsOrderByPaymentReference;

    public function __construct(
        private readonly ProvisionHotspotAccountAction $provisionHotspotAccount,
    ) {}

    /**
     * Handle the event.
     *
     * La diffusion temps réel Reverb du code d'accès est faite par
     * ProvisionHotspotAccountAction (HotspotAccountProvisioned).
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

        $this->provisionOnceForOrder($order);
    }

    /**
     * Un webhook GeniusPay redélivré, ou un chevauchement avec le job de
     * réconciliation, peut déclencher ce listener deux fois pour la même
     * commande avant que le premier passage n'ait fini. Le verrou empêche
     * deux comptes RouterOS réels d'être provisionnés pour un seul Order
     * (contrainte unique sur hotspot_accounts.order_id sinon violée).
     */
    private function provisionOnceForOrder(Order $order): void
    {
        if ($order->hotspotAccount) {
            return;
        }

        try {
            Cache::lock("hotspot-provisioning-order-{$order->id}", 30)->block(10, function () use ($order) {
                if (! $order->fresh()->hotspotAccount) {
                    $this->provisionHotspotAccount->handle($order);
                }
            });
        } catch (LockTimeoutException) {
            Log::info('Provisioning Hotspot déjà en cours pour cette commande par un autre processus', [
                'order_id' => $order->id,
            ]);
        }
    }
}
