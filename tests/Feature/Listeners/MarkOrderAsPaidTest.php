<?php

use App\Enums\OrderStatus;
use App\Listeners\MarkOrderAsPaid;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Order;
use GeniusPay\Laravel\Events\PaymentCompleted;

test('it marks the matching order as paid', function () {
    $order = Order::factory()->create([
        'status' => OrderStatus::Pending,
        'payment_reference' => 'PAY-123',
    ]);

    (new MarkOrderAsPaid)->handle(new PaymentCompleted(['reference' => 'PAY-123']));

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

    (new MarkOrderAsPaid)->handle(new PaymentCompleted(['reference' => 'PAY-123']));

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

    (new MarkOrderAsPaid)->handle(new PaymentCompleted(['reference' => 'PAY-123']));

    expect(Invoice::query()->where('order_id', $order->id)->exists())->toBeFalse();
});

test('it does nothing when no order matches the payment reference', function () {
    (new MarkOrderAsPaid)->handle(new PaymentCompleted(['reference' => 'UNKNOWN']));

    expect(Order::query()->count())->toBe(0);
});

test('it is idempotent when the order is already paid', function () {
    $customer = Customer::factory()->create();
    $order = Order::factory()->create([
        'customer_id' => $customer->id,
        'status' => OrderStatus::Paid,
        'payment_reference' => 'PAY-123',
    ]);

    (new MarkOrderAsPaid)->handle(new PaymentCompleted(['reference' => 'PAY-123']));

    expect(Invoice::query()->where('order_id', $order->id)->count())->toBe(0);
});
