<?php

use App\Enums\HotspotAccountStatus;
use App\Models\HotspotAccount;
use App\Models\User;
use Livewire\Livewire;

test('guests are redirected to the admin login screen', function () {
    $response = $this->get(route('admin.hotspot-accounts.index'));

    $response->assertRedirect(route('admin.login'));
});

test('an admin sees the hotspot accounts list', function () {
    $user = User::factory()->create();
    $account = HotspotAccount::factory()->create();

    $response = $this->actingAs($user)->get(route('admin.hotspot-accounts.index'));

    $response->assertOk();
    $response->assertSee($account->code);
});

test('an admin can suspend an active account', function () {
    $user = User::factory()->create();
    $account = HotspotAccount::factory()->create(['status' => HotspotAccountStatus::Active]);

    $this->actingAs($user);

    Livewire::test('pages::admin.hotspot-accounts.index')
        ->call('suspend', $account->id);

    expect($account->fresh()->status)->toBe(HotspotAccountStatus::Suspended);
});

test('an admin can reactivate a suspended account', function () {
    $user = User::factory()->create();
    $account = HotspotAccount::factory()->suspended()->create();

    $this->actingAs($user);

    Livewire::test('pages::admin.hotspot-accounts.index')
        ->call('reactivate', $account->id);

    expect($account->fresh()->status)->toBe(HotspotAccountStatus::Active);
});

test('an admin can extend an account still active from its current expiration', function () {
    $user = User::factory()->create();
    $expiresAt = now()->addHour();
    $account = HotspotAccount::factory()->create(['expires_at' => $expiresAt]);
    $account->load('order.package');
    $expectedMinutes = $account->order->package->duration_minutes;

    $this->actingAs($user);

    Livewire::test('pages::admin.hotspot-accounts.index')
        ->call('extend', $account->id);

    expect($account->fresh()->expires_at->timestamp)
        ->toBe($expiresAt->copy()->addMinutes($expectedMinutes)->timestamp);
});

test('an admin extending an already-expired account restarts it from now', function () {
    $user = User::factory()->create();
    $account = HotspotAccount::factory()->expired()->create();
    $account->load('order.package');
    $expectedMinutes = $account->order->package->duration_minutes;

    $this->actingAs($user);

    Livewire::test('pages::admin.hotspot-accounts.index')
        ->call('extend', $account->id);

    expect($account->fresh()->expires_at->timestamp)
        ->toBeGreaterThan(now()->addMinutes($expectedMinutes)->subMinute()->timestamp);
});
