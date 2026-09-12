<?php

use App\Models\Customer;
use App\Models\Package;

test('the recap page shows the chosen package details', function () {
    $package = Package::factory()->create(['name' => 'Forfait Test', 'price' => 1500]);

    $response = $this->get(route('orders.create', $package));

    $response->assertOk();
    $response->assertSee('Forfait Test');
    $response->assertSee('1 500 F');
});

test('the phone field is pre-filled for an authenticated customer', function () {
    $package = Package::factory()->create();
    $customer = Customer::factory()->create(['phone' => '0711223344']);

    $response = $this->actingAs($customer, 'customer')->get(route('orders.create', $package));

    $response->assertOk();
    $response->assertSee('0711223344');
});
