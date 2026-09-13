<?php

use App\Models\RouterSetting;
use App\Models\User;

test('an admin without a configured router is redirected to the router page from any other admin page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('admin.dashboard'))
        ->assertRedirect(route('admin.router.index'));

    $this->actingAs($user)->get(route('admin.packages.index'))
        ->assertRedirect(route('admin.router.index'));

    $this->actingAs($user)->get(route('admin.hotspot-accounts.index'))
        ->assertRedirect(route('admin.router.index'));

    $this->actingAs($user)->get(route('admin.customers.index'))
        ->assertRedirect(route('admin.router.index'));

    $this->actingAs($user)->get(route('admin.orders.index'))
        ->assertRedirect(route('admin.router.index'));

    $this->actingAs($user)->get(route('admin.trash.index'))
        ->assertRedirect(route('admin.router.index'));

    $this->actingAs($user)->get(route('admin.settings.edit'))
        ->assertRedirect(route('admin.router.index'));
});

test('an admin without a configured router can still reach the router page and log out', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get(route('admin.router.index'))->assertOk();

    $this->actingAs($user)->post(route('admin.logout'))
        ->assertRedirect();
});

test('an admin with a configured router can reach the rest of the administration', function () {
    RouterSetting::factory()->create();

    $user = User::factory()->create();

    $this->actingAs($user)->get(route('admin.dashboard'))->assertOk();
});
