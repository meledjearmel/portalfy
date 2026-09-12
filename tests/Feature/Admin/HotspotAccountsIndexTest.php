<?php

use App\Enums\HotspotAccountStatus;
use App\Models\HotspotAccount;
use App\Models\User;
use Livewire\Livewire;
use ZillEAli\MikrotikLaravel\Exceptions\ApiException;
use ZillEAli\MikrotikLaravel\Exceptions\ResourceNotFoundException;
use ZillEAli\MikrotikLaravel\Facades\MikroTik;
use ZillEAli\MikrotikLaravel\Services\HotspotManager;
use ZillEAli\MikrotikLaravel\Testing\MikrotikFake;

// Chaque `MikrotikFake::fake(['/ip/hotspot/user/print' => [...]])` ci-dessous
// fournit une ligne minimale pour que `HotspotManager` trouve un utilisateur
// (et son `.id`) quel que soit le nom recherché : le FakeRouterosClient
// ignore les filtres `queries` et renvoie simplement ces lignes telles
// quelles.

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

test('an admin can suspend an active account, disabling it and kicking its active session on RouterOS', function () {
    $fake = MikrotikFake::fake(['/ip/hotspot/user/print' => [['.id' => '*1', 'name' => 'ABC123']]]);
    $fake->forCommand('/ip/hotspot/active/print', [['.id' => '*1', 'user' => 'ABC123']]);

    $user = User::factory()->create();
    $account = HotspotAccount::factory()->create(['status' => HotspotAccountStatus::Active]);

    $this->actingAs($user);

    Livewire::test('pages::admin.hotspot-accounts.index')
        ->call('suspend', $account->id);

    $fake->assertQueried('/ip/hotspot/user/disable');
    $fake->assertQueried('/ip/hotspot/active/remove');

    expect($account->fresh()->status)->toBe(HotspotAccountStatus::Suspended);
});

test('an admin can suspend an account with no active session', function () {
    $fake = MikrotikFake::fake(['/ip/hotspot/user/print' => [['.id' => '*1', 'name' => 'ABC123']]]);
    // Pas de ligne pour '/ip/hotspot/active/print' : ResourceNotFoundException
    // lors de kickHost(), qui ne doit pas empêcher la suspension d'aboutir.

    $user = User::factory()->create();
    $account = HotspotAccount::factory()->create(['status' => HotspotAccountStatus::Active]);

    $this->actingAs($user);

    Livewire::test('pages::admin.hotspot-accounts.index')
        ->call('suspend', $account->id);

    $fake->assertQueried('/ip/hotspot/user/disable');

    expect($account->fresh()->status)->toBe(HotspotAccountStatus::Suspended);
});

test('suspending an account leaves it active locally when the router is unreachable', function () {
    MikroTik::shouldReceive('hotspot')->andReturnUsing(function () {
        $manager = Mockery::mock(HotspotManager::class);
        $manager->shouldReceive('disableUser')->andThrow(new ApiException('Router unreachable'));

        return $manager;
    });

    $user = User::factory()->create();
    $account = HotspotAccount::factory()->create(['status' => HotspotAccountStatus::Active]);

    $this->actingAs($user);

    Livewire::test('pages::admin.hotspot-accounts.index')
        ->call('suspend', $account->id);

    expect($account->fresh()->status)->toBe(HotspotAccountStatus::Active);
});

test('an admin can reactivate a suspended account, re-enabling it on RouterOS', function () {
    $fake = MikrotikFake::fake(['/ip/hotspot/user/print' => [['.id' => '*1', 'name' => 'ABC123']]]);

    $user = User::factory()->create();
    $account = HotspotAccount::factory()->suspended()->create();

    $this->actingAs($user);

    Livewire::test('pages::admin.hotspot-accounts.index')
        ->call('reactivate', $account->id);

    $fake->assertQueried('/ip/hotspot/user/enable');

    expect($account->fresh()->status)->toBe(HotspotAccountStatus::Active);
});

test('reactivating an account leaves it suspended locally when the router is unreachable', function () {
    MikroTik::shouldReceive('hotspot')->andReturnUsing(function () {
        $manager = Mockery::mock(HotspotManager::class);
        $manager->shouldReceive('enableUser')->andThrow(new ApiException('Router unreachable'));

        return $manager;
    });

    $user = User::factory()->create();
    $account = HotspotAccount::factory()->suspended()->create();

    $this->actingAs($user);

    Livewire::test('pages::admin.hotspot-accounts.index')
        ->call('reactivate', $account->id);

    expect($account->fresh()->status)->toBe(HotspotAccountStatus::Suspended);
});

test('an admin can extend an account still active from its current expiration, updating the RouterOS limit-uptime', function () {
    $fake = MikrotikFake::fake(['/ip/hotspot/user/print' => [['.id' => '*1', 'name' => 'ABC123']]]);

    $user = User::factory()->create();
    $activatedAt = now()->subHour();
    $expiresAt = now()->addHour();
    $account = HotspotAccount::factory()->create(['activated_at' => $activatedAt, 'expires_at' => $expiresAt]);
    $account->load('order.package');
    $expectedMinutes = $account->order->package->duration_minutes;
    $expectedExpiresAt = $expiresAt->copy()->addMinutes($expectedMinutes);

    $this->actingAs($user);

    Livewire::test('pages::admin.hotspot-accounts.index')
        ->call('extend', $account->id);

    $fake->assertQueried('/ip/hotspot/user/set');

    expect($account->fresh()->expires_at->timestamp)->toBe($expectedExpiresAt->timestamp);
});

test('an admin extending an already-expired account restarts it from now', function () {
    MikrotikFake::fake(['/ip/hotspot/user/print' => [['.id' => '*1', 'name' => 'ABC123']]]);

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

test('extending an account does not change its local expiration when the router is unreachable', function () {
    MikroTik::shouldReceive('hotspot')->andReturnUsing(function () {
        $manager = Mockery::mock(HotspotManager::class);
        $manager->shouldReceive('updateUser')->andThrow(new ApiException('Router unreachable'));

        return $manager;
    });

    $user = User::factory()->create();
    $expiresAt = now()->addHour();
    $account = HotspotAccount::factory()->create(['expires_at' => $expiresAt]);
    $account->load('order.package');

    $this->actingAs($user);

    Livewire::test('pages::admin.hotspot-accounts.index')
        ->call('extend', $account->id);

    expect($account->fresh()->expires_at->timestamp)->toBe($expiresAt->timestamp);
});

test('kickHost failures other than a missing session do not block the suspension', function () {
    // Un même mock est réutilisé pour les deux appels à hotspot() (disableUser
    // puis kickHost) : suspend() appelle MikroTik::hotspot() deux fois, et
    // andReturnUsing() créerait sinon une instance fraîche à chaque appel.
    $manager = Mockery::mock(HotspotManager::class);
    $manager->shouldReceive('disableUser')->once();
    $manager->shouldReceive('kickHost')->andThrow(new ApiException('Router unreachable'));

    MikroTik::shouldReceive('hotspot')->andReturn($manager);

    $user = User::factory()->create();
    $account = HotspotAccount::factory()->create(['status' => HotspotAccountStatus::Active]);

    $this->actingAs($user);

    Livewire::test('pages::admin.hotspot-accounts.index')
        ->call('suspend', $account->id);

    expect($account->fresh()->status)->toBe(HotspotAccountStatus::Suspended);
});

test('a missing hotspot user on the router blocks suspension instead of silently updating local status', function () {
    MikroTik::shouldReceive('hotspot')->andReturnUsing(function () {
        $manager = Mockery::mock(HotspotManager::class);
        $manager->shouldReceive('disableUser')->andThrow(ResourceNotFoundException::for('hotspot-user', 'ABC123'));

        return $manager;
    });

    $user = User::factory()->create();
    $account = HotspotAccount::factory()->create(['status' => HotspotAccountStatus::Active]);

    $this->actingAs($user);

    Livewire::test('pages::admin.hotspot-accounts.index')
        ->call('suspend', $account->id);

    expect($account->fresh()->status)->toBe(HotspotAccountStatus::Active);
});
