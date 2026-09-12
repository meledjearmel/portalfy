<?php

use App\Models\Package;
use App\Models\User;
use Livewire\Livewire;

test('guests are redirected to the admin login screen', function () {
    $response = $this->get(route('admin.trash.index'));

    $response->assertRedirect(route('admin.login'));
});

test('an admin sees soft-deleted packages in the trash', function () {
    $user = User::factory()->create();
    $package = Package::factory()->create(['name' => 'Forfait Supprimé']);
    $package->delete();

    $response = $this->actingAs($user)->get(route('admin.trash.index'));

    $response->assertOk();
    $response->assertSee('Forfait Supprimé');
});

test('an active package does not appear in the trash', function () {
    $user = User::factory()->create();
    Package::factory()->create(['name' => 'Forfait Actif']);

    $response = $this->actingAs($user)->get(route('admin.trash.index'));

    $response->assertOk();
    $response->assertDontSee('Forfait Actif');
});

test('an admin can restore a soft-deleted package', function () {
    $user = User::factory()->create();
    $package = Package::factory()->create();
    $package->delete();
    $this->actingAs($user);

    Livewire::test('pages::admin.trash.index')
        ->set('model', 'packages')
        ->call('restore', $package->id);

    expect($package->fresh())->not->toBeNull();
});

test('switching the model tab shows a different trashed collection', function () {
    $user = User::factory()->create();
    $package = Package::factory()->create(['name' => 'Forfait Corbeille']);
    $package->delete();
    $this->actingAs($user);

    Livewire::test('pages::admin.trash.index')
        ->set('model', 'customers')
        ->assertDontSee('Forfait Corbeille')
        ->set('model', 'packages')
        ->assertSee('Forfait Corbeille');
});
