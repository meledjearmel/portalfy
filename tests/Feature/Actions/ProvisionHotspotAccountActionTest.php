<?php

use App\Actions\ProvisionHotspotAccountAction;
use App\Enums\CredentialMode;
use App\Enums\HotspotAccountStatus;
use App\Events\HotspotAccountProvisioned;
use App\Models\Order;
use App\Models\Package;
use App\Models\WifiZoneSetting;
use Illuminate\Support\Facades\Event;
use ZillEAli\MikrotikLaravel\Exceptions\ApiException;
use ZillEAli\MikrotikLaravel\Facades\MikroTik;
use ZillEAli\MikrotikLaravel\Services\HotspotManager;
use ZillEAli\MikrotikLaravel\Testing\MikrotikFake;

test('it creates the RouterOS user and the local hotspot account when credential_mode is unique', function () {
    $fake = MikrotikFake::fake();

    WifiZoneSetting::current()->update(['credential_mode' => CredentialMode::Unique]);

    $package = Package::factory()->create(['duration_minutes' => 60, 'max_speed_mbps' => 5]);
    $order = Order::factory()->paid()->create(['package_id' => $package->id]);

    $account = (new ProvisionHotspotAccountAction)->handle($order);

    $fake->assertQueried('/ip/hotspot/user/add');

    expect($account)->not->toBeNull()
        ->and($account->status)->toBe(HotspotAccountStatus::Active)
        ->and($account->secret)->toBeNull()
        ->and($account->order_id)->toBe($order->id)
        ->and(strlen($account->code))->toBe(6);
});

test('it generates a separate secret when credential_mode is separate', function () {
    MikrotikFake::fake();

    WifiZoneSetting::current()->update(['credential_mode' => CredentialMode::Separate]);

    $order = Order::factory()->paid()->create();

    $account = (new ProvisionHotspotAccountAction)->handle($order);

    expect($account->secret)->not->toBeNull()
        ->and($account->secret)->not->toBe($account->code);
});

test('it broadcasts HotspotAccountProvisioned without leaking the secret', function () {
    Event::fake([HotspotAccountProvisioned::class]);
    MikrotikFake::fake();

    WifiZoneSetting::current()->update(['credential_mode' => CredentialMode::Separate]);

    $order = Order::factory()->paid()->create();

    $account = (new ProvisionHotspotAccountAction)->handle($order);

    Event::assertDispatched(HotspotAccountProvisioned::class, function ($event) use ($account) {
        return $event->hotspotAccount->is($account);
    });

    $broadcastPayload = (new HotspotAccountProvisioned($account))->broadcastWith();
    expect($broadcastPayload)->not->toHaveKey('secret')
        ->and($broadcastPayload['code'])->toBe($account->code);
});

test('it returns null and logs without throwing when the router is unreachable', function () {
    MikroTik::shouldReceive('hotspot')->andReturnUsing(function () {
        $manager = Mockery::mock(HotspotManager::class);
        $manager->shouldReceive('createUser')->andThrow(new ApiException('Router unreachable'));

        return $manager;
    });

    $order = Order::factory()->paid()->create();

    $account = (new ProvisionHotspotAccountAction)->handle($order);

    expect($account)->toBeNull();
    expect($order->fresh()->hotspotAccount)->toBeNull();
});
