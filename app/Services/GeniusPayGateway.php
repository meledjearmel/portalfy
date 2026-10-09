<?php

namespace App\Services;

use App\Contracts\PaymentGatewayContract;
use App\Contracts\PaymentInitiation;
use App\Enums\PaymentMethod;
use App\Models\Order;
use GeniusPay\Laravel\Facades\GeniusPay;

class GeniusPayGateway implements PaymentGatewayContract
{
    public function createPayment(Order $order, PaymentMethod $method): PaymentInitiation
    {
        $payment = GeniusPay::createPayment([
            'amount' => $order->amount,
            'product_reference' => $order->reference,
            'description' => "Accès WiFi — {$order->package->name}",
            'success_url' => route('orders.return', $order),
            'error_url' => route('orders.return', $order),
            'payment_method' => $method->value,
            'country' => 'CI',
            'customer' => [
                'phone' => '+225'.$order->phone,
            ],
        ]);

        return new PaymentInitiation(
            provider: 'geniuspay',
            reference: $payment->reference,
            // Mode direct : payment_url mène droit chez l'opérateur choisi.
            checkoutUrl: $payment->paymentUrl ?? $payment->checkoutUrl,
        );
    }

    public function getPaymentUrl(Order $order): string
    {
        $payment = GeniusPay::getPayment($order->payment_reference);

        return $payment->checkoutUrl;
    }
}
