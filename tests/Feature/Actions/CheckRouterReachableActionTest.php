<?php

use App\Actions\CheckRouterReachableAction;
use App\Models\RouterSetting;
use ZillEAli\MikrotikLaravel\Exceptions\ApiException;
use ZillEAli\MikrotikLaravel\Facades\MikroTik;
use ZillEAli\MikrotikLaravel\Services\SystemManager;

test('a router that answers is reachable', function () {
    RouterSetting::current()->update(['host' => '192.168.88.1', 'username' => 'admin']);
    $system = Mockery::mock(SystemManager::class);
    $system->shouldReceive('getIdentity')->once()->andReturn('MikroTik');
    MikroTik::shouldReceive('system')->andReturn($system);

    expect(app(CheckRouterReachableAction::class)->handle())->toBeTrue();
});

test('a router that does not answer is unreachable', function () {
    RouterSetting::current()->update(['host' => '192.168.88.1', 'username' => 'admin']);
    $system = Mockery::mock(SystemManager::class);
    $system->shouldReceive('getIdentity')->andThrow(new ApiException('Router unreachable'));
    MikroTik::shouldReceive('system')->andReturn($system);

    expect(app(CheckRouterReachableAction::class)->handle())->toBeFalse();
});

test('an unconfigured router is unreachable without attempting a connection', function () {
    MikroTik::shouldReceive('system')->never();

    expect(app(CheckRouterReachableAction::class)->handle())->toBeFalse();
});
