<?php

use App\Models\Customer;
use App\Models\Package;
use App\Models\User;
use App\Models\WifiZoneSetting;
use Database\Seeders\DatabaseSeeder;

beforeEach(function () {
    app()->detectEnvironment(fn (): string => 'production');
});

test('it seeds only essential data outside local', function () {
    app(DatabaseSeeder::class)->run();

    expect(WifiZoneSetting::query()->count())->toBe(1)
        ->and(Package::query()->count())->toBe(4)
        ->and(User::query()->count())->toBe(0)
        ->and(Customer::query()->count())->toBe(0);
});

test('it never overwrites existing configuration outside local', function () {
    WifiZoneSetting::query()->firstOrCreate(['id' => 1])->update(['name' => 'Ma Zone']);
    Package::factory()->create(['name' => 'Forfait admin']);

    app(DatabaseSeeder::class)->run();

    expect(WifiZoneSetting::query()->find(1)->name)->toBe('Ma Zone')
        ->and(Package::query()->pluck('name')->all())->toBe(['Forfait admin']);
});
