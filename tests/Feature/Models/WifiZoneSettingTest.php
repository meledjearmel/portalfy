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

    expect($zone->background_url)->toBe(Storage::url('wifi-zone/custom-bg.jpg'));
});
