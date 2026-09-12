<?php

namespace App\Services;

use App\Contracts\PaymentGatewayContract;
use App\Contracts\PaymentInitiation;
use App\Models\Order;
use GeniusPay\Laravel\Facades\GeniusPay;

class GeniusPayGateway implements PaymentGatewayContract
{
    public function createPayment(Order $order): PaymentInitiation
    {
        $payment = GeniusPay::createPayment([
            'amount' => $order->amount,
            'product_reference' => $order->reference,
            'description' => "Accès WiFi — {$order->package->name}",
            'success_url' => route('orders.return', $order),
            'error_url' => route('orders.return', $order),
            'customer' => [
                'phone' => $order->phone,
            ],
        ]);

        return new PaymentInitiation(
            provider: 'geniuspay',
            reference: $payment->reference,
            checkoutUrl: $payment->checkoutUrl,
        );
    }

    public function getPaymentUrl(Order $order): string
    {
        $payment = GeniusPay::getPayment($order->payment_reference);

        return $payment->checkoutUrl;
    }
}
