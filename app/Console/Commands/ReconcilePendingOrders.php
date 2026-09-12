<?php

namespace App\Console\Commands;

use App\Enums\OrderStatus;
use App\Models\Order;
use GeniusPay\Laravel\Events\PaymentCompleted;
use GeniusPay\Laravel\Events\PaymentFailed;
use GeniusPay\Laravel\Facades\GeniusPay;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

#[Signature('app:reconcile-pending-orders')]
#[Description("Rattrape les commandes restées 'pending' en interrogeant GeniusPay, au cas où son webhook aurait été manqué.")]
class ReconcilePendingOrders extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $orders = Order::query()
            ->where('status', OrderStatus::Pending)
            ->where('payment_provider', 'geniuspay')
            ->whereNotNull('payment_reference')
            ->where('created_at', '<=', now()->subMinutes(2))
            // Un checkout abandonné ne doit pas être interrogé indéfiniment :
            // au-delà de 24h, il n'y a plus lieu d'attendre un webhook tardif.
            ->where('created_at', '>=', now()->subDay())
            ->get();

        foreach ($orders as $order) {
            try {
                $payment = GeniusPay::getPayment($order->payment_reference);

                if ($payment->isCompleted()) {
                    PaymentCompleted::dispatch(['reference' => $order->payment_reference]);
                } elseif ($payment->isFailed()) {
                    PaymentFailed::dispatch(['reference' => $order->payment_reference]);
                }
            } catch (\Throwable $e) {
                // N'importe quelle étape (appel GeniusPay, ou les listeners
                // synchrones déclenchés par le dispatch) peut échouer pour
                // une commande sans que ça doive interrompre le traitement
                // des suivantes.
                Log::error('Réconciliation GeniusPay : échec pour une commande, poursuite avec les suivantes', [
                    'order_id' => $order->id,
                    'error' => $e->getMessage(),
                ]);
            }
        }
    }
}
