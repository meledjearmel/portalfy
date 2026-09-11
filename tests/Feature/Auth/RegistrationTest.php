<?php

use App\Models\Order;
use Laravel\Fortify\Features;

beforeEach(function () {
    $this->skipUnlessFortifyHas(Features::registration());
});

test('registration screen can be rendered', function () {
    $response = $this->get(route('register'));

    $response->assertOk();
});

test('new customers can register', function () {
    $response = $this->post(route('register.store'), [
        'email' => 'test@example.com',
        'phone' => '0700000000',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertSessionHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticated('customer');
});

test('registering attaches anonymous orders placed with the same phone number', function () {
    $order = Order::factory()->create([
        'phone' => '0700000000',
        'customer_id' => null,
    ]);

    $this->post(route('register.store'), [
        'email' => 'test@example.com',
        'phone' => '0700000000',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    expect($order->fresh()->customer_id)->toBe(auth('customer')->id());
});
