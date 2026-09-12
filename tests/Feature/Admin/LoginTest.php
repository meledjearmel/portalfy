<?php

use App\Models\Customer;
use App\Models\User;
use Livewire\Livewire;

test('the admin login screen can be rendered', function () {
    $response = $this->get(route('admin.login'));

    $response->assertOk();
});

test('an admin can authenticate using valid credentials', function () {
    $user = User::factory()->create();

    Livewire::test('pages::admin.auth.login')
        ->set('email', $user->email)
        ->set('password', 'password')
        ->call('login')
        ->assertHasNoErrors()
        ->assertRedirect(route('admin.dashboard'));

    $this->assertAuthenticatedAs($user, 'web');
});

test('an admin can not authenticate with an invalid password', function () {
    $user = User::factory()->create();

    Livewire::test('pages::admin.auth.login')
        ->set('email', $user->email)
        ->set('password', 'wrong-password')
        ->call('login')
        ->assertHasErrors('email');

    $this->assertGuest('web');
});

test('an authenticated customer can not access the admin area', function () {
    $customer = Customer::factory()->create();

    $response = $this->actingAs($customer, 'customer')->get(route('admin.dashboard'));

    $response->assertRedirect(route('admin.login'));
});

test('a guest hitting an admin route is redirected to the admin login, not the customer one', function () {
    $response = $this->get(route('admin.dashboard'));

    $response->assertRedirect(route('admin.login'));
});

test('a guest hitting a web-guard route outside /admin is still redirected to the admin login', function () {
    // /settings/* est protégé par auth:web (paramètres du compte admin) mais
    // ne commence pas par /admin : un aiguillage basé sur le seul préfixe
    // d'URL renverrait ici vers la page de connexion CLIENT, provoquant une
    // boucle de redirection puisque auth:web ne sera jamais satisfait par une
    // connexion customer.
    $response = $this->get(route('profile.edit'));

    $response->assertRedirect(route('admin.login'));
});
