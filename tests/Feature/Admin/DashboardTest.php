<?php

use App\Models\HotspotAccount;
use App\Models\Order;
use App\Models\User;

test('guests are redirected to the admin login screen', function () {
    $response = $this->get(route('admin.dashboard'));

    $response->assertRedirect(route('admin.login'));
});

test('an authenticated admin sees revenue, sales count and active accounts', function () {
    $user = User::factory()->create();

    $paidOrder = Order::factory()->paid()->create(['amount' => 1000]);
    Order::factory()->paid()->create(['amount' => 2000]);
    Order::factory()->create(); // pending, excluded from revenue/sales

    // Rattachée à une commande déjà comptée ci-dessus : la factory
    // HotspotAccount crée sinon sa propre commande payée avec un montant
    // aléatoire, faussant le total attendu.
    HotspotAccount::factory()->create(['order_id' => $paidOrder->id]);

    $response = $this->actingAs($user)->get(route('admin.dashboard'));

    $response->assertOk();
    $response->assertSee('3 000 F');
    $response->assertSee('Ventes');
});

test('recent orders are listed with a readable status', function () {
    $user = User::factory()->create();
    $order = Order::factory()->paid()->create();

    $response = $this->actingAs($user)->get(route('admin.dashboard'));

    $response->assertOk();
    $response->assertSee($order->package->name);
    $response->assertSee('Payée');
});
