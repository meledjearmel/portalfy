<?php

use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Support\Facades\Http;
use ZillEAli\MikrotikLaravel\Testing\MikrotikFake;

beforeEach(function () {
    // Une commande reconciliée en "Paid" déclenche aussi le provisioning
    // RouterOS : neutralise toute tentative de connexion réseau réelle.
    MikrotikFake::fake();
});

test('it marks a completed order as paid via GeniusPay lookup', function () {
    $order = Order::factory()->create([
        'status' => OrderStatus::Pending,
        'payment_provider' => 'geniuspay',
        'payment_reference' => 'PAY-COMPLETED',
        'created_at' => now()->subMinutes(5),
    ]);

    Http::fake([
        '*/payments/PAY-COMPLETED' => Http::response([
            'data' => ['reference' => 'PAY-COMPLETED', 'status' => 'completed'],
        ]),
    ]);

    $this->artisan('app:reconcile-pending-orders')->assertSuccessful();

    expect($order->fresh()->status)->toBe(OrderStatus::Paid);
});

test('it marks a failed order as failed via GeniusPay lookup', function () {
    $order = Order::factory()->create([
        'status' => OrderStatus::Pending,
        'payment_provider' => 'geniuspay',
        'payment_reference' => 'PAY-FAILED',
        'created_at' => now()->subMinutes(5),
    ]);

    Http::fake([
        '*/payments/PAY-FAILED' => Http::response([
            'data' => ['reference' => 'PAY-FAILED', 'status' => 'failed'],
        ]),
    ]);

    $this->artisan('app:reconcile-pending-orders')->assertSuccessful();

    expect($order->fresh()->status)->toBe(OrderStatus::Failed);
});

test('it leaves a still-pending order untouched', function () {
    $order = Order::factory()->create([
        'status' => OrderStatus::Pending,
        'payment_provider' => 'geniuspay',
        'payment_reference' => 'PAY-STILL-PENDING',
        'created_at' => now()->subMinutes(5),
    ]);

    Http::fake([
        '*/payments/PAY-STILL-PENDING' => Http::response([
            'data' => ['reference' => 'PAY-STILL-PENDING', 'status' => 'pending'],
        ]),
    ]);

    $this->artisan('app:reconcile-pending-orders')->assertSuccessful();

    expect($order->fresh()->status)->toBe(OrderStatus::Pending);
});

test('it ignores orders that are too recent to reconcile yet', function () {
    Http::fake();

    Order::factory()->create([
        'status' => OrderStatus::Pending,
        'payment_provider' => 'geniuspay',
        'payment_reference' => 'PAY-TOO-RECENT',
        'created_at' => now(),
    ]);

    $this->artisan('app:reconcile-pending-orders')->assertSuccessful();

    Http::assertNothingSent();
});

test('it ignores abandoned orders older than 24 hours', function () {
    Http::fake();

    Order::factory()->create([
        'status' => OrderStatus::Pending,
        'payment_provider' => 'geniuspay',
        'payment_reference' => 'PAY-TOO-OLD',
        'created_at' => now()->subDays(2),
    ]);

    $this->artisan('app:reconcile-pending-orders')->assertSuccessful();

    Http::assertNothingSent();
});

test('it ignores pending orders from a different payment provider', function () {
    Http::fake();

    Order::factory()->create([
        'status' => OrderStatus::Pending,
        'payment_provider' => 'some-other-gateway',
        'payment_reference' => 'PAY-OTHER-PROVIDER',
        'created_at' => now()->subMinutes(5),
    ]);

    $this->artisan('app:reconcile-pending-orders')->assertSuccessful();

    Http::assertNothingSent();
});

test('a failure processing one order does not interrupt reconciliation of the others', function () {
    $unreachable = Order::factory()->create([
        'status' => OrderStatus::Pending,
        'payment_provider' => 'geniuspay',
        'payment_reference' => 'PAY-UNREACHABLE',
        'created_at' => now()->subMinutes(5),
    ]);
    $completed = Order::factory()->create([
        'status' => OrderStatus::Pending,
        'payment_provider' => 'geniuspay',
        'payment_reference' => 'PAY-COMPLETED-2',
        'created_at' => now()->subMinutes(5),
    ]);

    Http::fake([
        '*/payments/PAY-UNREACHABLE' => Http::response(['message' => 'Erreur API'], 500),
        '*/payments/PAY-COMPLETED-2' => Http::response([
            'data' => ['reference' => 'PAY-COMPLETED-2', 'status' => 'completed'],
        ]),
    ]);

    $this->artisan('app:reconcile-pending-orders')->assertSuccessful();

    expect($unreachable->fresh()->status)->toBe(OrderStatus::Pending)
        ->and($completed->fresh()->status)->toBe(OrderStatus::Paid);
});
