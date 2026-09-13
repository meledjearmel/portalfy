<?php

use App\Models\Customer;
use App\Models\HotspotAccount;
use App\Models\Order;
use App\Models\RouterSetting;
use App\Models\User;

// L'écran client est derrière EnsureRouterIsConfigured.
beforeEach(fn () => RouterSetting::factory()->create());

test('guests are redirected to the admin login screen', function () {
    $customer = Customer::factory()->create();

    $response = $this->get(route('admin.customers.show', $customer));

    $response->assertRedirect(route('admin.login'));
});

test('an admin sees the customer profile and their order history', function () {
    $user = User::factory()->create();
    $customer = Customer::factory()->create(['email' => 'client@example.com']);
    $order = Order::factory()->paid()->create(['customer_id' => $customer->id]);
    HotspotAccount::factory()->create(['order_id' => $order->id, 'code' => 'ABCDEF']);

    $response = $this->actingAs($user)->get(route('admin.customers.show', $customer));

    $response->assertOk();
    $response->assertSee('client@example.com');
    $response->assertSee($order->reference);
    $response->assertSee('ABCDEF');
});

test('a customer with no orders shows an empty state instead of an empty table', function () {
    $user = User::factory()->create();
    $customer = Customer::factory()->create();

    $response = $this->actingAs($user)->get(route('admin.customers.show', $customer));

    $response->assertOk();
    $response->assertSee("n'a passé aucune commande", false);
});

test('orders from another customer are not shown', function () {
    $user = User::factory()->create();
    $customer = Customer::factory()->create();
    $otherCustomer = Customer::factory()->create();
    $ownOrder = Order::factory()->paid()->create(['customer_id' => $customer->id]);
    $otherOrder = Order::factory()->paid()->create(['customer_id' => $otherCustomer->id]);

    $response = $this->actingAs($user)->get(route('admin.customers.show', $customer));

    $response->assertOk();
    $response->assertSee($ownOrder->reference);
    $response->assertDontSee($otherOrder->reference);
});
