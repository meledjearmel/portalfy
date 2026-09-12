<?php

use App\Models\WifiZoneSetting;

test('the homepage renders the wifi zone name and both call to action links', function () {
    $zone = WifiZoneSetting::current();

    $response = $this->get(route('home'));

    $response->assertOk();
    $response->assertSee($zone->name);
    $response->assertSee(route('packages.index'), false);
    $response->assertSee(route('hotspot-access.show'), false);
});
