<?php

use App\Models\Customer;
use Laravel\Fortify\Features;

test('login screen can be rendered', function () {
    $response = $this->get(route('login'));

    $response->assertOk();
});

test('customers can authenticate using the login screen', function () {
    $customer = Customer::factory()->create();

    $response = $this->post(route('login.store'), [
        'email' => $customer->email,
        'password' => 'password',
    ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('account.profile', absolute: false));

    $this->assertAuthenticated('customer');
});

test('customers can not authenticate with invalid password', function () {
    $customer = Customer::factory()->create();

    $response = $this->post(route('login.store'), [
        'email' => $customer->email,
        'password' => 'wrong-password',
    ]);

    $response->assertSessionHasErrorsIn('email');

    $this->assertGuest('customer');
});

test('customers with two factor enabled are redirected to two factor challenge', function () {
    $this->skipUnlessFortifyHas(Features::twoFactorAuthentication());

    Features::twoFactorAuthentication([
        'confirm' => true,
        'confirmPassword' => true,
    ]);

    $customer = Customer::factory()->create();

    $response = $this->post(route('login.store'), [
        'email' => $customer->email,
        'password' => 'password',
    ]);

    $response->assertRedirect(route('two-factor.login'));
    $this->assertGuest('customer');
});

test('customers can logout', function () {
    $customer = Customer::factory()->create();

    $response = $this->actingAs($customer, 'customer')->post(route('logout'));

    $response->assertRedirect(route('home'));

    $this->assertGuest('customer');
});
