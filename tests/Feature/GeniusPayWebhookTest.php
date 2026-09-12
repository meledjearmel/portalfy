<?php

use App\Enums\OrderStatus;
use App\Models\Order;
use ZillEAli\MikrotikLaravel\Testing\MikrotikFake;

test('the geniuspay webhook marks the matching order as paid end to end', function () {
    // Le paiement confirmé déclenche aussi le provisioning RouterOS :
    // neutralise toute tentative de connexion réseau réelle.
    MikrotikFake::fake();

    $order = Order::factory()->create([
        'status' => OrderStatus::Pending,
        'payment_reference' => 'PAY-123',
    ]);

    $payload = json_encode([
        'event' => 'payment.completed',
        'data' => ['reference' => 'PAY-123', 'amount' => $order->amount],
    ]);

    $signature = hash_hmac('sha256', $payload, config('geniuspay.webhook_secret'));

    $response = $this->call(
        'POST',
        route('geniuspay.webhook'),
        server: ['CONTENT_TYPE' => 'application/json', 'HTTP_X-GeniusPay-Signature' => $signature],
        content: $payload,
    );

    $response->assertOk();
    expect($order->fresh()->status)->toBe(OrderStatus::Paid);
});

test('the geniuspay webhook rejects an invalid signature', function () {
    $order = Order::factory()->create([
        'status' => OrderStatus::Pending,
        'payment_reference' => 'PAY-123',
    ]);

    $payload = json_encode([
        'event' => 'payment.completed',
        'data' => ['reference' => 'PAY-123'],
    ]);

    $response = $this->call(
        'POST',
        route('geniuspay.webhook'),
        server: ['CONTENT_TYPE' => 'application/json', 'HTTP_X-GeniusPay-Signature' => 'not-the-right-signature'],
        content: $payload,
    );

    $response->assertStatus(401);
    expect($order->fresh()->status)->toBe(OrderStatus::Pending);
});
