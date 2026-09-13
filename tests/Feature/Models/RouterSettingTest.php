<?php

use App\Models\RouterSetting;
use Illuminate\Support\Facades\DB;

test('current creates an empty row on first access, without reading any value from .env', function () {
    $setting = RouterSetting::current();

    expect($setting->host)->toBeNull()
        ->and($setting->username)->toBeNull()
        ->and($setting->isConfigured())->toBeFalse();
});

test('current returns the persisted row on subsequent calls instead of recreating it', function () {
    RouterSetting::factory()->create(['host' => '192.168.1.1']);

    $setting = RouterSetting::current();

    expect($setting->host)->toBe('192.168.1.1')
        ->and(RouterSetting::query()->count())->toBe(1);
});

test('isConfigured requires both a host and a username', function () {
    expect(RouterSetting::factory()->make(['host' => null, 'username' => 'admin'])->isConfigured())->toBeFalse()
        ->and(RouterSetting::factory()->make(['host' => '192.168.88.1', 'username' => null])->isConfigured())->toBeFalse()
        ->and(RouterSetting::factory()->make(['host' => '192.168.88.1', 'username' => 'admin'])->isConfigured())->toBeTrue();
});

test('the password attribute is encrypted at rest', function () {
    $setting = RouterSetting::factory()->create(['password' => 'super-secret']);

    $raw = DB::table('router_settings')->find($setting->id)->password;

    expect($raw)->not->toBe('super-secret')
        ->and($setting->fresh()->password)->toBe('super-secret');
});

test('toMikrotikConfig maps the settings row to the mikrotik-laravel config shape', function () {
    $setting = RouterSetting::factory()->create([
        'host' => '192.168.88.1',
        'port' => 8728,
        'username' => 'admin',
        'password' => 'secret',
        'timeout' => 10,
        'use_ssl' => true,
    ]);

    expect($setting->toMikrotikConfig())->toBe([
        'host' => '192.168.88.1',
        'port' => 8728,
        'username' => 'admin',
        'password' => 'secret',
        'timeout' => 10,
        'ssl' => true,
    ]);
});

test('toMikrotikConfig returns an empty string password instead of null', function () {
    $setting = RouterSetting::factory()->create(['password' => null]);

    expect($setting->toMikrotikConfig()['password'])->toBe('');
});
