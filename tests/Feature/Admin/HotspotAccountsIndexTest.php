<?php

use App\Enums\HotspotAccountStatus;
use App\Models\Customer;
use App\Models\HotspotAccount;
use App\Models\Order;
use App\Models\Package;
use App\Models\RouterSetting;
use App\Models\User;
use Livewire\Livewire;
use ZillEAli\MikrotikLaravel\Exceptions\ApiException;
use ZillEAli\MikrotikLaravel\Exceptions\ResourceNotFoundException;
use ZillEAli\MikrotikLaravel\Facades\MikroTik;
use ZillEAli\MikrotikLaravel\Services\HotspotManager;
use ZillEAli\MikrotikLaravel\Testing\MikrotikFake;

// La liste des comptes Hotspot est derrière EnsureRouterIsConfigured.
beforeEach(fn () => RouterSetting::factory()->create());

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

test('the search narrows accounts down by code', function () {
    $matching = HotspotAccount::factory()->create(['code' => 'FINDME']);
    $other = HotspotAccount::factory()->create(['code' => 'OTHER1']);

    Livewire::test('pages::admin.hotspot-accounts.index')
        ->set('search', 'findme')
        ->assertSee($matching->code)
        ->assertDontSee($other->code);
});

test('the search narrows accounts down by package name', function () {
    $package = Package::factory()->create(['name' => 'Forfait Journalier Unique']);
    $matching = HotspotAccount::factory()->voucher()->create(['package_id' => $package->id, 'code' => 'ABCDEF']);
    $other = HotspotAccount::factory()->create(['code' => 'ZYXWVU']);

    Livewire::test('pages::admin.hotspot-accounts.index')
        ->set('search', 'Journalier')
        ->assertSee($matching->code)
        ->assertDontSee($other->code);
});

test('the search narrows accounts down by the linked customer email or order phone', function () {
    $customer = Customer::factory()->create(['email' => 'alice@example.com']);
    $order = Order::factory()->paid()->create(['customer_id' => $customer->id, 'phone' => '0711111111']);
    $matching = HotspotAccount::factory()->create(['order_id' => $order->id, 'code' => 'ABCDEF']);

    $otherOrder = Order::factory()->paid()->create(['phone' => '0722222222']);
    $other = HotspotAccount::factory()->create(['order_id' => $otherOrder->id, 'code' => 'ZYXWVU']);

    Livewire::test('pages::admin.hotspot-accounts.index')
        ->set('search', 'alice')
        ->assertSee($matching->code)
        ->assertDontSee($other->code);
});

test('a search matching nothing shows a search-specific empty state', function () {
    HotspotAccount::factory()->create();

    Livewire::test('pages::admin.hotspot-accounts.index')
        ->set('search', 'no-such-code')
        ->assertSee('Aucun compte Hotspot ne correspond à cette recherche.');
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

test('an admin can generate vouchers for a package, without any order', function () {
    $fake = MikrotikFake::fake();
    $package = Package::factory()->create();

    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test('pages::admin.hotspot-accounts.index')
        ->set('voucherPackageId', $package->id)
        ->set('voucherQuantity', 3)
        ->call('generateVouchers')
        ->assertHasNoErrors();

    // Un compte créé = une commande /add (le forfait peut aussi déclencher
    // des commandes de profil de vitesse, voir CreateHotspotAccountActionTest).
    $userAddCount = count(array_filter(
        $fake->recordedQueries(),
        fn (string $command) => $command === '/ip/hotspot/user/add',
    ));
    expect($userAddCount)->toBe(3);

    $vouchers = HotspotAccount::query()->whereNull('order_id')->get();
    expect($vouchers)->toHaveCount(3);
    expect($vouchers->every(fn ($v) => $v->package_id === $package->id))->toBeTrue();
});

test('generating vouchers requires a package and a valid quantity', function () {
    MikrotikFake::fake();

    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test('pages::admin.hotspot-accounts.index')
        ->set('voucherPackageId', null)
        ->set('voucherQuantity', 0)
        ->call('generateVouchers')
        ->assertHasErrors(['voucherPackageId', 'voucherQuantity']);

    expect(HotspotAccount::query()->count())->toBe(0);
});

test('a partial router failure still keeps the vouchers that were created', function () {
    // Le premier createUser réussit, le second échoue : la génération ne
    // doit pas être tout-ou-rien, contrairement à une transaction classique.
    $manager = Mockery::mock(HotspotManager::class);
    $manager->shouldReceive('createUser')->once();
    $manager->shouldReceive('createUser')->andThrow(new ApiException('Router unreachable'));

    MikroTik::shouldReceive('hotspot')->andReturn($manager);

    // Pas de limite de débit ici : évite d'avoir aussi à stubber
    // getProfiles()/createProfile() sur ce mock, hors sujet de ce test.
    $package = Package::factory()->create(['max_speed_mbps' => null]);
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test('pages::admin.hotspot-accounts.index')
        ->set('voucherPackageId', $package->id)
        ->set('voucherQuantity', 2)
        ->call('generateVouchers');

    expect(HotspotAccount::query()->count())->toBe(1);
});

test('an admin can move a hotspot account to the trash', function () {
    $account = HotspotAccount::factory()->create();

    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test('pages::admin.hotspot-accounts.index')
        ->call('delete', $account->id);

    expect(HotspotAccount::query()->find($account->id))->toBeNull()
        ->and(HotspotAccount::withTrashed()->find($account->id))->not->toBeNull();
});

test('extending a voucher uses its own package, since it has no order', function () {
    $fake = MikrotikFake::fake(['/ip/hotspot/user/print' => [['.id' => '*1', 'name' => 'ABC123']]]);

    $package = Package::factory()->create(['duration_minutes' => 120]);
    $account = HotspotAccount::factory()->voucher()->create([
        'package_id' => $package->id,
        'expires_at' => now()->addHour(),
    ]);

    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test('pages::admin.hotspot-accounts.index')
        ->call('extend', $account->id);

    $fake->assertQueried('/ip/hotspot/user/set');

    expect($account->fresh()->expires_at->timestamp)
        ->toBe($account->expires_at->copy()->addMinutes(120)->timestamp);
});
