<?php

use App\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Package;
use App\Models\User;
use Livewire\Livewire;

test('guests are redirected to the admin login screen', function () {
    $response = $this->get(route('admin.orders.index'));

    $response->assertRedirect(route('admin.login'));
});

test('an admin sees all orders regardless of status', function () {
    $user = User::factory()->create();
    Order::factory()->paid()->create(['package_id' => Package::factory()->create(['name' => 'Forfait Payé'])]);
    Order::factory()->failed()->create(['package_id' => Package::factory()->create(['name' => 'Forfait Échoué'])]);

    $response = $this->actingAs($user)->get(route('admin.orders.index'));

    $response->assertOk();
    $response->assertSee('Forfait Payé');
    $response->assertSee('Forfait Échoué');
});

test('the status filter narrows down the list', function () {
    Order::factory()->paid()->create(['package_id' => Package::factory()->create(['name' => 'Forfait Payé'])]);
    Order::factory()->failed()->create(['package_id' => Package::factory()->create(['name' => 'Forfait Échoué'])]);

    Livewire::test('pages::admin.orders.index')
        ->set('status', OrderStatus::Paid->value)
        ->assertSee('Forfait Payé')
        ->assertDontSee('Forfait Échoué');
});
