<?php

use App\Enums\OrderStatus;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Package;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

test('the recap page shows the chosen package details', function () {
    $package = Package::factory()->create(['name' => 'Forfait Test', 'price' => 1500]);

    $response = $this->get(route('orders.create', $package));

    $response->assertOk();
    $response->assertSee('Forfait Test');
    $response->assertSee('1 500 F');
});

test('the phone field is pre-filled for an authenticated customer', function () {
    $package = Package::factory()->create();
    $customer = Customer::factory()->create(['phone' => '0711223344']);

    $response = $this->actingAs($customer, 'customer')->get(route('orders.create', $package));

    $response->assertOk();
    $response->assertSee('0711223344');
});

test('paying creates a pending order, initiates a GeniusPay payment and redirects to checkout', function () {
    Http::fake([
        '*/payments' => Http::response([
            'data' => [
                'reference' => 'PAY-123',
                'product_reference' => 'ORDER-REF',
                'checkout_url' => 'https://pay.geniuspay.io/checkout/PAY-123',
                'status' => 'pending',
            ],
        ]),
    ]);

    $package = Package::factory()->create(['price' => 2000]);

    Livewire::test('pages::orders.create', ['package' => $package])
        ->set('phone', '0700000000')
        ->call('pay')
        ->assertRedirect('https://pay.geniuspay.io/checkout/PAY-123');

    $order = Order::query()->sole();

    expect($order->package_id)->toBe($package->id)
        ->and($order->phone)->toBe('0700000000')
        ->and($order->amount)->toBe(2000)
        ->and($order->status)->toBe(OrderStatus::Pending)
        ->and($order->payment_provider)->toBe('geniuspay')
        ->and($order->payment_reference)->toBe('PAY-123');
});

test('a phone number that does not match the Ivorian format is rejected', function () {
    $package = Package::factory()->create();

    Livewire::test('pages::orders.create', ['package' => $package])
        ->set('phone', '0912345678')
        ->call('pay')
        ->assertHasErrors(['phone' => 'regex']);

    expect(Order::query()->count())->toBe(0);
});

test('spaces typed by the input mask are stripped before validation', function () {
    Http::fake([
        '*/payments' => Http::response([
            'data' => ['reference' => 'PAY-123', 'checkout_url' => 'https://pay.geniuspay.io/checkout/PAY-123'],
        ]),
    ]);

    $package = Package::factory()->create();

    Livewire::test('pages::orders.create', ['package' => $package])
        ->set('phone', '07 00 00 00 00')
        ->call('pay')
        ->assertHasNoErrors();

    expect(Order::query()->sole()->phone)->toBe('0700000000');
});

test('a failure to reach the payment gateway shows a clear error and does not leave an orphan order', function () {
    Http::fake([
        '*/payments' => Http::response(['message' => 'Erreur API GeniusPay'], 500),
    ]);

    $package = Package::factory()->create();

    Livewire::test('pages::orders.create', ['package' => $package])
        ->set('phone', '0700000000')
        ->call('pay')
        ->assertHasErrors('phone');

    expect(Order::query()->withTrashed()->count())->toBe(0);
});
