<?php

namespace App\Listeners;

use App\Enums\OrderStatus;
use App\Events\AdminDashboardActivity;
use App\Listeners\Concerns\FindsOrderByPaymentReference;
use GeniusPay\Laravel\Events\PaymentFailed;

class MarkOrderAsFailed
{
    use FindsOrderByPaymentReference;

    /**
     * Handle the event.
     */
    public function handle(PaymentFailed $event): void
    {
        $order = $this->findOrder($event->getReference());

        if (! $order || $order->status !== OrderStatus::Pending) {
            return;
        }

        $order->update(['status' => OrderStatus::Failed]);

        AdminDashboardActivity::dispatch();
    }
}
