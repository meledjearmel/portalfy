<?php

use App\Models\Customer;
use App\Models\RouterSetting;
use App\Models\User;
use Livewire\Livewire;

// La liste des clients est derrière EnsureRouterIsConfigured.
beforeEach(fn () => RouterSetting::factory()->create());

test('guests are redirected to the admin login screen', function () {
    $response = $this->get(route('admin.customers.index'));

    $response->assertRedirect(route('admin.login'));
});

test('an admin sees the customers list', function () {
    $user = User::factory()->create();
    $customer = Customer::factory()->create(['email' => 'client@example.com']);

    $response = $this->actingAs($user)->get(route('admin.customers.index'));

    $response->assertOk();
    $response->assertSee('client@example.com');
});

test('the search narrows customers down by email or phone', function () {
    Customer::factory()->create(['email' => 'alice@example.com', 'phone' => '0700000001']);
    Customer::factory()->create(['email' => 'bob@example.com', 'phone' => '0700000002']);

    Livewire::test('pages::admin.customers.index')
        ->set('search', 'alice')
        ->assertSee('alice@example.com')
        ->assertDontSee('bob@example.com');
});
