<?php

use App\Models\Package;
use App\Models\RouterSetting;
use App\Models\User;
use Livewire\Livewire;

// La liste des forfaits est derrière EnsureRouterIsConfigured : créer un
// forfait n'a de sens qu'une fois un routeur enregistré (voir routes/admin.php).
beforeEach(fn () => RouterSetting::factory()->create());

test('guests are redirected to the admin login screen', function () {
    $response = $this->get(route('admin.packages.index'));

    $response->assertRedirect(route('admin.login'));
});

test('an admin sees the packages list', function () {
    $user = User::factory()->create();
    $package = Package::factory()->create(['name' => 'Forfait Journalier']);

    $response = $this->actingAs($user)->get(route('admin.packages.index'));

    $response->assertOk();
    $response->assertSee('Forfait Journalier');
});

test('an admin can create a package', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test('pages::admin.packages.index')
        ->set('name', 'Forfait Test')
        ->set('price', 1000)
        ->set('duration_minutes', 60)
        ->set('max_devices', 2)
        ->set('sort_order', 1)
        ->call('save')
        ->assertHasNoErrors();

    expect(Package::query()->where('name', 'Forfait Test')->exists())->toBeTrue();
});

test('an admin can edit an existing package', function () {
    $user = User::factory()->create();
    $package = Package::factory()->create(['name' => 'Ancien nom', 'is_active' => true]);
    $this->actingAs($user);

    Livewire::test('pages::admin.packages.index')
        ->call('edit', $package->id)
        ->set('name', 'Nouveau nom')
        ->set('is_active', false)
        ->call('save')
        ->assertHasNoErrors();

    expect($package->fresh()->name)->toBe('Nouveau nom')
        ->and($package->fresh()->is_active)->toBeFalse();
});

test('an admin can soft delete a package', function () {
    $user = User::factory()->create();
    $package = Package::factory()->create();
    $this->actingAs($user);

    Livewire::test('pages::admin.packages.index')
        ->call('delete', $package->id);

    expect(Package::query()->find($package->id))->toBeNull()
        ->and(Package::withTrashed()->find($package->id))->not->toBeNull();
});

test('creating a package requires a name', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test('pages::admin.packages.index')
        ->set('name', '')
        ->set('price', 1000)
        ->set('duration_minutes', 60)
        ->call('save')
        ->assertHasErrors('name');
});
