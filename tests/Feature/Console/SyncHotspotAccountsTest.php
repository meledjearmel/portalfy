<?php

use App\Console\Commands\SyncHotspotAccounts;
use App\Enums\HotspotAccountStatus;
use App\Models\HotspotAccount;
use ZillEAli\MikrotikLaravel\Exceptions\ApiException;
use ZillEAli\MikrotikLaravel\Facades\MikroTik;
use ZillEAli\MikrotikLaravel\Services\HotspotManager;

function mockHotspot(): HotspotManager
{
    $hotspot = Mockery::mock(HotspotManager::class);
    MikroTik::shouldReceive('hotspot')->andReturn($hotspot);

    return $hotspot;
}

test('a code used directly on the router is marked as started, without any end date', function () {
    $this->travelTo(now()->startOfMinute());
    $account = HotspotAccount::factory()->voucher()->create(['code' => 'ROUTE2']);
    $untouched = HotspotAccount::factory()->voucher()->create(['code' => 'WAIT22']);

    mockHotspot()->shouldReceive('getUsers')->andReturn([
        ['name' => 'ROUTE2', 'uptime' => '10m', 'limit-uptime' => '1h'],
        ['name' => 'WAIT22', 'uptime' => '0s', 'limit-uptime' => '1h'],
    ]);

    $this->artisan(SyncHotspotAccounts::class)->assertSuccessful();

    expect($account->fresh()->activated_at->toDateTimeString())->toBe(now()->subMinutes(10)->toDateTimeString())
        ->and($account->fresh()->expires_at)->toBeNull()
        ->and($account->fresh()->status)->toBe(HotspotAccountStatus::Active)
        ->and($untouched->fresh()->activated_at)->toBeNull();
});

test('a code that has consumed all its connection time is marked as exhausted', function () {
    $account = HotspotAccount::factory()->voucher()->create(['code' => 'DONE22', 'activated_at' => now()->subDay()]);

    mockHotspot()->shouldReceive('getUsers')->andReturn([
        ['name' => 'DONE22', 'uptime' => '1h', 'limit-uptime' => '1h'],
    ]);

    $this->artisan(SyncHotspotAccounts::class)->assertSuccessful();

    expect($account->fresh()->status)->toBe(HotspotAccountStatus::Used);
});

test('a started code is never cut because of elapsed wall-clock time', function () {
    $account = HotspotAccount::factory()->create(['expires_at' => now()->subDay()]);

    $hotspot = mockHotspot();
    $hotspot->shouldReceive('getUsers')->andReturn([
        ['name' => $account->code, 'uptime' => '20m', 'limit-uptime' => '1h'],
    ]);
    $hotspot->shouldNotReceive('disableUser');

    $this->artisan(SyncHotspotAccounts::class)->assertSuccessful();

    expect($account->fresh()->status)->toBe(HotspotAccountStatus::Active);
});

test('an unreachable router leaves every code unchanged', function () {
    $account = HotspotAccount::factory()->voucher()->create();

    mockHotspot()->shouldReceive('getUsers')->andThrow(new ApiException('injoignable'));

    $this->artisan(SyncHotspotAccounts::class)->assertSuccessful();

    expect($account->fresh()->activated_at)->toBeNull();
});

test('router uptimes are converted to seconds', function (string $uptime, int $seconds) {
    expect(SyncHotspotAccounts::parseUptime($uptime))->toBe($seconds);
})->with([
    ['0s', 0],
    ['45m', 2700],
    ['1h2m3s', 3723],
    ['1w2d', 777600],
]);
