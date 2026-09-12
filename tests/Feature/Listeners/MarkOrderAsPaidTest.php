<?php

use App\Enums\HotspotAccountStatus;
use App\Enums\OrderStatus;
use App\Listeners\MarkOrderAsPaid;
use App\Models\Customer;
use App\Models\HotspotAccount;
use App\Models\Invoice;
use App\Models\Order;
use GeniusPay\Laravel\Events\PaymentCompleted;
use ZillEAli\MikrotikLaravel\Testing\MikrotikFake;

beforeEach(function () {
    // Le listener déclenche aussi le provisioning RouterOS : neutralise
    // toute tentative de connexion réseau réelle pendant ces tests.
    MikrotikFake::fake();

    $this->handle = fn (PaymentCompleted $event) => app(MarkOrderAsPaid::class)->handle($event);
});

test('it marks the matching order as paid', function () {
    $order = Order::factory()->create([
        'status' => OrderStatus::Pending,
        'payment_reference' => 'PAY-123',
    ]);

    ($this->handle)(new PaymentCompleted(['reference' => 'PAY-123']));

    expect($order->fresh()->status)->toBe(OrderStatus::Paid);
});

test('it creates an invoice when the order belongs to a customer', function () {
    $customer = Customer::factory()->create();
    $order = Order::factory()->create([
        'customer_id' => $customer->id,
        'status' => OrderStatus::Pending,
        'payment_reference' => 'PAY-123',
        'amount' => 2000,
    ]);

    ($this->handle)(new PaymentCompleted(['reference' => 'PAY-123']));

    $invoice = Invoice::query()->where('order_id', $order->id)->sole();
    expect($invoice->amount)->toBe(2000)
        ->and($invoice->number)->toBe(1);
});

test('it does not create an invoice for an anonymous order', function () {
    $order = Order::factory()->create([
        'customer_id' => null,
        'status' => OrderStatus::Pending,
        'payment_reference' => 'PAY-123',
    ]);

    ($this->handle)(new PaymentCompleted(['reference' => 'PAY-123']));

    expect(Invoice::query()->where('order_id', $order->id)->exists())->toBeFalse();
});

test('it does nothing when no order matches the payment reference', function () {
    ($this->handle)(new PaymentCompleted(['reference' => 'UNKNOWN']));

    expect(Order::query()->count())->toBe(0);
});

test('it is idempotent when the order is already paid', function () {
    $customer = Customer::factory()->create();
    $order = Order::factory()->create([
        'customer_id' => $customer->id,
        'status' => OrderStatus::Paid,
        'payment_reference' => 'PAY-123',
    ]);

    ($this->handle)(new PaymentCompleted(['reference' => 'PAY-123']));

    expect(Invoice::query()->where('order_id', $order->id)->count())->toBe(0);
});

test('it provisions a hotspot account for the newly paid order', function () {
    $order = Order::factory()->create([
        'status' => OrderStatus::Pending,
        'payment_reference' => 'PAY-123',
    ]);

    ($this->handle)(new PaymentCompleted(['reference' => 'PAY-123']));

    expect($order->fresh()->hotspotAccount)->not->toBeNull();
});

test('it does not provision a second hotspot account for an order that already has one', function () {
    // Simule un webhook GeniusPay redélivré après que le premier passage
    // ait déjà provisionné le compte : ne doit jamais violer la contrainte
    // unique hotspot_accounts.order_id ni créer un second compte RouterOS réel.
    $order = Order::factory()->create([
        'status' => OrderStatus::Pending,
        'payment_reference' => 'PAY-123',
    ]);
    HotspotAccount::factory()->create([
        'order_id' => $order->id,
        'status' => HotspotAccountStatus::Active,
    ]);

    ($this->handle)(new PaymentCompleted(['reference' => 'PAY-123']));

    expect($order->fresh()->status)->toBe(OrderStatus::Paid)
        ->and(HotspotAccount::query()->where('order_id', $order->id)->count())->toBe(1);
});
