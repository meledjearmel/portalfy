<?php

use App\Console\Commands\SyncHotspotAccounts;
use App\Enums\HotspotAccountStatus;
use App\Models\HotspotAccount;
use ZillEAli\MikrotikLaravel\Exceptions\ApiException;
use ZillEAli\MikrotikLaravel\Exceptions\ResourceNotFoundException;
use ZillEAli\MikrotikLaravel\Facades\MikroTik;
use ZillEAli\MikrotikLaravel\Services\HotspotManager;

function mockHotspot(): HotspotManager
{
    $hotspot = Mockery::mock(HotspotManager::class);
    MikroTik::shouldReceive('hotspot')->andReturn($hotspot);

    return $hotspot;
}

test('a code used directly on the router starts when its first connection happened', function () {
    $this->travelTo(now()->startOfMinute());
    $account = HotspotAccount::factory()->voucher()->create(['code' => 'ROUTE2']);
    $account->package->update(['duration_minutes' => 60]);
    $untouched = HotspotAccount::factory()->voucher()->create(['code' => 'WAIT22']);

    mockHotspot()->shouldReceive('getUsers')->andReturn([
        ['name' => 'ROUTE2', 'uptime' => '10m'],
        ['name' => 'WAIT22', 'uptime' => '0s'],
    ]);

    $this->artisan(SyncHotspotAccounts::class)->assertSuccessful();

    expect($account->fresh()->activated_at->toDateTimeString())->toBe(now()->subMinutes(10)->toDateTimeString())
        ->and($account->fresh()->expires_at->toDateTimeString())->toBe(now()->addMinutes(50)->toDateTimeString())
        ->and($untouched->fresh()->activated_at)->toBeNull();
});

test('a code whose duration has elapsed is cut on the router then marked expired', function () {
    $account = HotspotAccount::factory()->create(['expires_at' => now()->subMinute()]);

    $hotspot = mockHotspot();
    $hotspot->shouldReceive('disableUser')->once()->with($account->code);
    $hotspot->shouldReceive('kickHost')->once()->andThrow(new ResourceNotFoundException('aucune session'));

    $this->artisan(SyncHotspotAccounts::class)->assertSuccessful();

    expect($account->fresh()->status)->toBe(HotspotAccountStatus::Expired);
});

test('an elapsed code stays active locally when the router cannot cut it', function () {
    $account = HotspotAccount::factory()->create(['expires_at' => now()->subMinute()]);

    mockHotspot()->shouldReceive('disableUser')->andThrow(new ApiException('injoignable'));

    $this->artisan(SyncHotspotAccounts::class)->assertSuccessful();

    expect($account->fresh()->status)->toBe(HotspotAccountStatus::Active);
});

test('codes that have not started or are still running are left alone', function () {
    HotspotAccount::factory()->voucher()->create();
    HotspotAccount::factory()->create(['expires_at' => now()->addHour()]);

    $hotspot = mockHotspot();
    $hotspot->shouldReceive('getUsers')->andReturn([]);
    $hotspot->shouldNotReceive('disableUser');

    $this->artisan(SyncHotspotAccounts::class)->assertSuccessful();
});

test('router uptimes are converted to seconds', function (string $uptime, int $seconds) {
    expect(SyncHotspotAccounts::parseUptime($uptime))->toBe($seconds);
})->with([
    ['0s', 0],
    ['45m', 2700],
    ['1h2m3s', 3723],
    ['1w2d', 777600],
]);
