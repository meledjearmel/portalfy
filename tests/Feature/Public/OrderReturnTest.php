<?php

use App\Enums\OrderStatus;
use App\Models\HotspotAccount;
use App\Models\Order;

test('a pending order shows the verification screen', function () {
    $order = Order::factory()->create(['status' => OrderStatus::Pending]);

    $response = $this->get(route('orders.return', $order));

    $response->assertOk();
    $response->assertSee('Vérification de votre paiement');
});

test('a paid order without a hotspot account yet shows an activation-in-progress message', function () {
    $order = Order::factory()->paid()->create();

    $response = $this->get(route('orders.return', $order));

    $response->assertOk();
    $response->assertSee('Paiement réussi');
    $response->assertSee('en cours d');
});

test('a paid order with a hotspot account shows the access code', function () {
    $order = Order::factory()->paid()->create();
    $account = HotspotAccount::factory()->create(['order_id' => $order->id]);

    $response = $this->get(route('orders.return', $order));

    $response->assertOk();
    $response->assertSee($account->code);
});

test('a failed order shows a retry option', function () {
    $order = Order::factory()->failed()->create();

    $response = $this->get(route('orders.return', $order));

    $response->assertOk();
    $response->assertSee('Le paiement a échoué');
});
