<?php

use App\Models\Customer;
use App\Models\HotspotAccount;
use App\Models\Order;

test('guests are redirected to the login screen', function () {
    $account = HotspotAccount::factory()->create();

    $response = $this->get(route('account.access.show', $account));

    $response->assertRedirect(route('login'));
});

test('the owner of the hotspot account can see its code', function () {
    $customer = Customer::factory()->create();
    $order = Order::factory()->paid()->create(['customer_id' => $customer->id]);
    $account = HotspotAccount::factory()->create(['order_id' => $order->id]);

    $response = $this->actingAs($customer, 'customer')->get(route('account.access.show', $account));

    $response->assertOk();
    $response->assertSee($account->code);
});

test('a customer can not view another customer\'s hotspot account', function () {
    $customer = Customer::factory()->create();
    $otherCustomer = Customer::factory()->create();
    $order = Order::factory()->paid()->create(['customer_id' => $otherCustomer->id]);
    $account = HotspotAccount::factory()->create(['order_id' => $order->id]);

    $response = $this->actingAs($customer, 'customer')->get(route('account.access.show', $account));

    $response->assertForbidden();
});
