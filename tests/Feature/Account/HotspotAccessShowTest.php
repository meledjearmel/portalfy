<?php

use App\Enums\HotspotAccountStatus;
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

test('an active, non-expired access shows its real status and a connect-to-wifi action', function () {
    $customer = Customer::factory()->create();
    $order = Order::factory()->paid()->create(['customer_id' => $customer->id]);
    $account = HotspotAccount::factory()->create([
        'order_id' => $order->id,
        'status' => HotspotAccountStatus::Active,
        'expires_at' => now()->addHour(),
    ]);

    $response = $this->actingAs($customer, 'customer')->get(route('account.access.show', $account));

    $response->assertOk();
    $response->assertSee('Actif');
    $response->assertSee('Connecte-toi au wifi');
});

test('an expired access shows its real status without offering to connect', function () {
    $customer = Customer::factory()->create();
    $order = Order::factory()->paid()->create(['customer_id' => $customer->id]);
    $account = HotspotAccount::factory()->expired()->create(['order_id' => $order->id]);

    $response = $this->actingAs($customer, 'customer')->get(route('account.access.show', $account));

    $response->assertOk();
    $response->assertSee('Expiré');
    $response->assertDontSee('Se connecter au WiFi');
    $response->assertSee('Copier le code');
});

test('a suspended access shows its real status without offering to connect', function () {
    $customer = Customer::factory()->create();
    $order = Order::factory()->paid()->create(['customer_id' => $customer->id]);
    $account = HotspotAccount::factory()->suspended()->create(['order_id' => $order->id]);

    $response = $this->actingAs($customer, 'customer')->get(route('account.access.show', $account));

    $response->assertOk();
    $response->assertSee('Suspendu');
    $response->assertDontSee('Se connecter au WiFi');
});

test('a customer can not view another customer\'s hotspot account', function () {
    $customer = Customer::factory()->create();
    $otherCustomer = Customer::factory()->create();
    $order = Order::factory()->paid()->create(['customer_id' => $otherCustomer->id]);
    $account = HotspotAccount::factory()->create(['order_id' => $order->id]);

    $response = $this->actingAs($customer, 'customer')->get(route('account.access.show', $account));

    $response->assertForbidden();
});
