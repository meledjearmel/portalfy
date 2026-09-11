<?php

use App\Models\Customer;
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

test('registering does not auto-attach anonymous orders placed with the same phone number', function () {
    // Auto-attaching by phone alone would let anyone claim another
    // person's purchase history and hotspot codes just by knowing their
    // phone number. Linking past orders needs its own verified mechanism
    // (see étape 8), not an automatic side effect of registration.
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

    expect($order->fresh()->customer_id)->toBeNull();
});

test('a deleted customer email and phone can be reused to register again', function () {
    $deleted = Customer::factory()->create([
        'email' => 'test@example.com',
        'phone' => '0700000000',
    ]);
    $deleted->delete();

    $response = $this->post(route('register.store'), [
        'email' => 'test@example.com',
        'phone' => '0700000000',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $response->assertSessionHasNoErrors()
        ->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticated('customer');
    expect(auth('customer')->id())->not->toBe($deleted->id);
});
