<?php

use App\Enums\CustomerStatus;
use App\Models\Customer;
use App\Models\RouterSetting;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
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

test('an admin can create a customer', function () {
    Livewire::test('pages::admin.customers.index')
        ->set('email', 'nouveau@example.com')
        ->set('phone', '0700000099')
        ->set('status', CustomerStatus::Active->value)
        ->set('password', 'un-mot-de-passe-solide')
        ->set('password_confirmation', 'un-mot-de-passe-solide')
        ->call('save')
        ->assertHasNoErrors();

    $customer = Customer::query()->where('email', 'nouveau@example.com')->first();
    expect($customer)->not->toBeNull()
        ->and($customer->phone)->toBe('0700000099')
        ->and(Hash::check('un-mot-de-passe-solide', $customer->password))->toBeTrue();
});

test('creating a customer requires a unique email', function () {
    Customer::factory()->create(['email' => 'existe@example.com']);

    Livewire::test('pages::admin.customers.index')
        ->set('email', 'existe@example.com')
        ->set('phone', '0700000099')
        ->set('password', 'un-mot-de-passe-solide')
        ->set('password_confirmation', 'un-mot-de-passe-solide')
        ->call('save')
        ->assertHasErrors('email');
});

test('creating a customer requires a valid ivorian phone number', function () {
    Livewire::test('pages::admin.customers.index')
        ->set('email', 'nouveau@example.com')
        ->set('phone', '123')
        ->set('password', 'un-mot-de-passe-solide')
        ->set('password_confirmation', 'un-mot-de-passe-solide')
        ->call('save')
        ->assertHasErrors('phone');
});

test('an admin can edit a customer without changing the password by default', function () {
    $customer = Customer::factory()->create(['email' => 'ancien@example.com']);
    $originalPassword = $customer->password;

    Livewire::test('pages::admin.customers.index')
        ->call('edit', $customer->id)
        ->set('email', 'nouveau@example.com')
        ->set('status', CustomerStatus::Suspended->value)
        ->call('save')
        ->assertHasNoErrors();

    $customer->refresh();
    expect($customer->email)->toBe('nouveau@example.com')
        ->and($customer->status)->toBe(CustomerStatus::Suspended)
        ->and($customer->password)->toBe($originalPassword);
});

test('an admin can set a new password while editing a customer', function () {
    $customer = Customer::factory()->create();

    Livewire::test('pages::admin.customers.index')
        ->call('edit', $customer->id)
        ->set('password', 'nouveau-mot-de-passe')
        ->set('password_confirmation', 'nouveau-mot-de-passe')
        ->call('save')
        ->assertHasNoErrors();

    expect(Hash::check('nouveau-mot-de-passe', $customer->fresh()->password))->toBeTrue();
});

test('an admin can move a customer to the trash', function () {
    $customer = Customer::factory()->create();

    Livewire::test('pages::admin.customers.index')
        ->call('delete', $customer->id);

    expect(Customer::query()->find($customer->id))->toBeNull()
        ->and(Customer::withTrashed()->find($customer->id))->not->toBeNull();
});
