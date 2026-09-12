<?php

use App\Models\Customer;

test('guests are redirected to the login screen', function () {
    $response = $this->get(route('account.profile'));

    $response->assertRedirect(route('login'));
});

test('an authenticated customer sees their own email and phone', function () {
    $customer = Customer::factory()->create([
        'email' => 'client@example.com',
        'phone' => '0700000000',
    ]);

    $response = $this->actingAs($customer, 'customer')->get(route('account.profile'));

    $response->assertOk();
    $response->assertSee('client@example.com');
    $response->assertSee('0700000000');
});
