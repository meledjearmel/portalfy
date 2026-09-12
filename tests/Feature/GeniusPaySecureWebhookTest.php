<?php

use App\Enums\OrderStatus;
use App\Models\Order;
use ZillEAli\MikrotikLaravel\Testing\MikrotikFake;

function signGeniusPayPayload(string $payload, string $timestamp): string
{
    return hash_hmac('sha256', $timestamp.'.'.$payload, config('geniuspay.webhook_secret'));
}

test('the geniuspay webhook marks the matching order as paid end to end', function () {
    // Le paiement confirmé déclenche aussi le provisioning RouterOS :
    // neutralise toute tentative de connexion réseau réelle.
    MikrotikFake::fake();

    $order = Order::factory()->create([
        'status' => OrderStatus::Pending,
        'payment_reference' => 'PAY-123',
    ]);

    $payload = ['event' => 'payment.success', 'data' => ['reference' => 'PAY-123', 'amount' => $order->amount]];
    $timestamp = (string) time();
    $signature = signGeniusPayPayload(json_encode($payload), $timestamp);

    $response = $this->call(
        'POST',
        route('webhooks.geniuspay'),
        server: [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X-Webhook-Signature' => $signature,
            'HTTP_X-Webhook-Timestamp' => $timestamp,
        ],
        content: json_encode($payload),
    );

    $response->assertOk();
    expect($order->fresh()->status)->toBe(OrderStatus::Paid);
});

test('the geniuspay webhook marks the matching order as failed', function () {
    $order = Order::factory()->create([
        'status' => OrderStatus::Pending,
        'payment_reference' => 'PAY-456',
    ]);

    $payload = ['event' => 'payment.failed', 'data' => ['reference' => 'PAY-456']];
    $timestamp = (string) time();
    $signature = signGeniusPayPayload(json_encode($payload), $timestamp);

    $response = $this->call(
        'POST',
        route('webhooks.geniuspay'),
        server: [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X-Webhook-Signature' => $signature,
            'HTTP_X-Webhook-Timestamp' => $timestamp,
        ],
        content: json_encode($payload),
    );

    $response->assertOk();
    expect($order->fresh()->status)->toBe(OrderStatus::Failed);
});

test('the geniuspay webhook rejects an invalid signature', function () {
    $order = Order::factory()->create([
        'status' => OrderStatus::Pending,
        'payment_reference' => 'PAY-123',
    ]);

    $payload = ['event' => 'payment.success', 'data' => ['reference' => 'PAY-123']];
    $timestamp = (string) time();

    $response = $this->call(
        'POST',
        route('webhooks.geniuspay'),
        server: [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X-Webhook-Signature' => 'not-the-right-signature',
            'HTTP_X-Webhook-Timestamp' => $timestamp,
        ],
        content: json_encode($payload),
    );

    $response->assertStatus(401);
    expect($order->fresh()->status)->toBe(OrderStatus::Pending);
});

test('the geniuspay webhook rejects a stale timestamp', function () {
    $order = Order::factory()->create([
        'status' => OrderStatus::Pending,
        'payment_reference' => 'PAY-123',
    ]);

    $payload = ['event' => 'payment.success', 'data' => ['reference' => 'PAY-123']];
    $timestamp = (string) (time() - 600);
    $signature = signGeniusPayPayload(json_encode($payload), $timestamp);

    $response = $this->call(
        'POST',
        route('webhooks.geniuspay'),
        server: [
            'CONTENT_TYPE' => 'application/json',
            'HTTP_X-Webhook-Signature' => $signature,
            'HTTP_X-Webhook-Timestamp' => $timestamp,
        ],
        content: json_encode($payload),
    );

    $response->assertStatus(400);
    expect($order->fresh()->status)->toBe(OrderStatus::Pending);
});
