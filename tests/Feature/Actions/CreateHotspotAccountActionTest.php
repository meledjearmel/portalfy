<?php

use App\Actions\CreateHotspotAccountAction;
use App\Enums\CredentialMode;
use App\Enums\HotspotAccountStatus;
use App\Models\HotspotAccount;
use App\Models\Order;
use App\Models\Package;
use App\Models\WifiZoneSetting;
use ZillEAli\MikrotikLaravel\Exceptions\ApiException;
use ZillEAli\MikrotikLaravel\Facades\MikroTik;
use ZillEAli\MikrotikLaravel\Services\HotspotManager;
use ZillEAli\MikrotikLaravel\Testing\MikrotikFake;

test('it creates a RouterOS user and a local hotspot account for a voucher, without an order', function () {
    $fake = MikrotikFake::fake();
    WifiZoneSetting::current()->update(['credential_mode' => CredentialMode::Unique]);

    $package = Package::factory()->create(['duration_minutes' => 60, 'max_speed_mbps' => 5]);

    $account = (new CreateHotspotAccountAction)->handle($package);

    $fake->assertQueried('/ip/hotspot/user/add');

    expect($account)->not->toBeNull()
        ->and($account->order_id)->toBeNull()
        ->and($account->package_id)->toBe($package->id)
        ->and($account->isVoucher())->toBeTrue()
        ->and($account->status)->toBe(HotspotAccountStatus::Active)
        ->and(strlen($account->code))->toBe(6);
});

test('it creates a hotspot account tied to an order when one is given', function () {
    MikrotikFake::fake();

    $order = Order::factory()->paid()->create();

    $account = (new CreateHotspotAccountAction)->handle($order->package, $order);

    expect($account->order_id)->toBe($order->id)
        ->and($account->package_id)->toBe($order->package_id)
        ->and($account->isVoucher())->toBeFalse();
});

test('it returns null and logs without throwing when the router is unreachable', function () {
    MikroTik::shouldReceive('hotspot')->andReturnUsing(function () {
        $manager = Mockery::mock(HotspotManager::class);
        $manager->shouldReceive('createUser')->andThrow(new ApiException('Router unreachable'));

        return $manager;
    });

    $account = (new CreateHotspotAccountAction)->handle(Package::factory()->create());

    expect($account)->toBeNull();
});

test('it removes the RouterOS user when the local account cannot be saved', function () {
    $manager = Mockery::mock(HotspotManager::class);
    $manager->shouldReceive('createUser')->once();
    $manager->shouldReceive('deleteUser')->once();
    MikroTik::shouldReceive('hotspot')->andReturn($manager);

    HotspotAccount::creating(fn () => throw new RuntimeException('Database unavailable'));

    $account = (new CreateHotspotAccountAction)->handle(Package::factory()->create(['max_speed_mbps' => null]));

    expect($account)->toBeNull()
        ->and(HotspotAccount::count())->toBe(0);
});

test('it does not try to remove a RouterOS user that was never created', function () {
    MikroTik::shouldReceive('hotspot')->andReturnUsing(function () {
        $manager = Mockery::mock(HotspotManager::class);
        $manager->shouldReceive('createUser')->andThrow(new ApiException('Router unreachable'));
        $manager->shouldNotReceive('deleteUser');

        return $manager;
    });

    expect((new CreateHotspotAccountAction)->handle(Package::factory()->create(['max_speed_mbps' => null])))->toBeNull();
});

// RouterOS rejette `rate-limit` posé directement sur un utilisateur Hotspot
// sur certaines versions (vérifié sur un routeur réel en 7.24) : la limite de
// débit passe donc par un profil Hotspot référencé via `profile`, jamais un
// champ `rate-limit` direct sur /ip/hotspot/user/add.

test('a package with a speed limit creates a matching hotspot profile and references it', function () {
    $fake = MikrotikFake::fake();

    $package = Package::factory()->create(['max_speed_mbps' => 5]);

    (new CreateHotspotAccountAction)->handle($package);

    $fake->assertQueried('/ip/hotspot/user/profile/print');
    $fake->assertQueried('/ip/hotspot/user/profile/add');
    $fake->assertQueried('/ip/hotspot/user/add');
});

test('it reuses an existing hotspot profile instead of creating a duplicate', function () {
    $fake = MikrotikFake::fake([
        '/ip/hotspot/user/profile/print' => [['name' => 'portalfy-5M', 'rate-limit' => '5M/5M']],
    ]);

    $package = Package::factory()->create(['max_speed_mbps' => 5]);

    (new CreateHotspotAccountAction)->handle($package);

    $fake->assertQueried('/ip/hotspot/user/profile/print');
    $fake->assertNotQueried('/ip/hotspot/user/profile/add');
});

test('a package without a speed limit never touches hotspot profiles', function () {
    $fake = MikrotikFake::fake();

    $package = Package::factory()->create(['max_speed_mbps' => null]);

    (new CreateHotspotAccountAction)->handle($package);

    $fake->assertNotQueried('/ip/hotspot/user/profile/print');
    $fake->assertNotQueried('/ip/hotspot/user/profile/add');
});

test('generating several accounts for the same package only creates the RouterOS profile once', function () {
    $fake = MikrotikFake::fake();

    $package = Package::factory()->create(['max_speed_mbps' => 5]);
    $action = new CreateHotspotAccountAction;

    $action->handle($package);
    $action->handle($package);
    $action->handle($package);

    $profileAddCount = count(array_filter(
        $fake->recordedQueries(),
        fn (string $command) => $command === '/ip/hotspot/user/profile/add',
    ));

    expect($profileAddCount)->toBe(1);
});
