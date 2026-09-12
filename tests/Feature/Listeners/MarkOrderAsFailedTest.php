<?php

use App\Enums\OrderStatus;
use App\Listeners\MarkOrderAsFailed;
use App\Models\Order;
use GeniusPay\Laravel\Events\PaymentFailed;

test('it marks the matching order as failed', function () {
    $order = Order::factory()->create([
        'status' => OrderStatus::Pending,
        'payment_reference' => 'PAY-123',
    ]);

    (new MarkOrderAsFailed)->handle(new PaymentFailed(['reference' => 'PAY-123']));

    expect($order->fresh()->status)->toBe(OrderStatus::Failed);
});

test('it never downgrades an already paid order', function () {
    $order = Order::factory()->create([
        'status' => OrderStatus::Paid,
        'payment_reference' => 'PAY-123',
    ]);

    (new MarkOrderAsFailed)->handle(new PaymentFailed(['reference' => 'PAY-123']));

    expect($order->fresh()->status)->toBe(OrderStatus::Paid);
});
