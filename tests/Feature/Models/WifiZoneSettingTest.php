<?php

use App\Models\WifiZoneSetting;
use Illuminate\Support\Facades\Storage;

test('background_url falls back to the default theme image when none is configured', function () {
    $zone = WifiZoneSetting::factory()->create(['background_path' => null]);

    expect($zone->background_url)->toContain('hero-background.jpg');
});

test('background_url uses the admin-configured file when one is set', function () {
    Storage::fake('public');

    $zone = WifiZoneSetting::factory()->create(['background_path' => 'wifi-zone/custom-bg.jpg']);

    expect($zone->background_url)->toBe(Storage::disk('public')->url('wifi-zone/custom-bg.jpg'));
});

test('logo_url is empty when no logo is configured', function () {
    $zone = WifiZoneSetting::factory()->create(['logo_path' => null]);

    expect($zone->logo_url)->toBe('');
});

test('logo_url uses the admin-configured file when one is set', function () {
    Storage::fake('public');

    $zone = WifiZoneSetting::factory()->create(['logo_path' => 'wifi-zone/logo.png']);

    expect($zone->logo_url)->toBe(Storage::disk('public')->url('wifi-zone/logo.png'));
});
