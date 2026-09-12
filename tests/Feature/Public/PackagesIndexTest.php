<?php

use App\Models\Package;

test('the packages list only shows active packages, ordered by sort_order', function () {
    $second = Package::factory()->create(['name' => 'Second', 'is_active' => true, 'sort_order' => 2]);
    $first = Package::factory()->create(['name' => 'First', 'is_active' => true, 'sort_order' => 1]);
    Package::factory()->inactive()->create(['name' => 'Hidden']);

    $response = $this->get(route('packages.index'));

    $response->assertOk();
    $response->assertSeeInOrder([$first->name, $second->name]);
    $response->assertDontSee('Hidden');
});

test('a package shows its duration in a human-readable label', function () {
    $package = Package::factory()->create(['duration_minutes' => 1440]);

    $response = $this->get(route('packages.index'));

    $response->assertOk();
    $response->assertSee('1 jour');
});
