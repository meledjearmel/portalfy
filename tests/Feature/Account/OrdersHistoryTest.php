<?php

use App\Models\Customer;
use App\Models\HotspotAccount;
use App\Models\Order;
use App\Models\Package;

test('guests are redirected to the login screen', function () {
    $response = $this->get(route('account.orders'));

    $response->assertRedirect(route('login'));
});

test('a customer only sees their own orders', function () {
    $customer = Customer::factory()->create();
    $otherCustomer = Customer::factory()->create();

    Order::factory()->create([
        'customer_id' => $customer->id,
        'package_id' => Package::factory()->create(['name' => 'Forfait Mien']),
    ]);
    Order::factory()->create([
        'customer_id' => $otherCustomer->id,
        'package_id' => Package::factory()->create(['name' => 'Forfait Autrui']),
    ]);

    $response = $this->actingAs($customer, 'customer')->get(route('account.orders'));

    $response->assertOk();
    $response->assertSee('Forfait Mien');
    $response->assertDontSee('Forfait Autrui');
});

test('a paid order with an active non-expired hotspot account is shown as active', function () {
    $customer = Customer::factory()->create();
    $order = Order::factory()->paid()->create(['customer_id' => $customer->id]);
    HotspotAccount::factory()->create(['order_id' => $order->id]);

    $response = $this->actingAs($customer, 'customer')->get(route('account.orders'));

    $response->assertOk();
    $response->assertSee('Actif');
});

test('a paid order with an expired hotspot account is shown as expired', function () {
    $customer = Customer::factory()->create();
    $order = Order::factory()->paid()->create(['customer_id' => $customer->id]);
    HotspotAccount::factory()->expired()->create(['order_id' => $order->id]);

    $response = $this->actingAs($customer, 'customer')->get(route('account.orders'));

    $response->assertOk();
    $response->assertSee('Expiré');
});

test('a paid order without a hotspot account yet is shown as being activated', function () {
    $customer = Customer::factory()->create();
    Order::factory()->paid()->create(['customer_id' => $customer->id]);

    $response = $this->actingAs($customer, 'customer')->get(route('account.orders'));

    $response->assertOk();
    $response->assertSee('Activation en cours');
});

test('a failed order shows a clear failure label', function () {
    $customer = Customer::factory()->create();
    Order::factory()->failed()->create(['customer_id' => $customer->id]);

    $response = $this->actingAs($customer, 'customer')->get(route('account.orders'));

    $response->assertOk();
    $response->assertSee('Paiement échoué');
});
