<?php

use App\Enums\CredentialMode;
use App\Models\RouterSetting;
use App\Models\User;
use App\Models\WifiZoneSetting;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

// Les paramètres de la zone WiFi sont derrière EnsureRouterIsConfigured.
beforeEach(fn () => RouterSetting::factory()->create());

test('guests are redirected to the admin login screen', function () {
    $response = $this->get(route('admin.settings.edit'));

    $response->assertRedirect(route('admin.login'));
});

test('an admin sees the current zone settings prefilled', function () {
    $user = User::factory()->create();
    WifiZoneSetting::current()->update(['name' => 'Mon Cyber Café']);

    $response = $this->actingAs($user)->get(route('admin.settings.edit'));
    $response->assertOk();

    // Flux hydrate la valeur des champs wire:model côté client : le HTML
    // servi ne contient pas "Mon Cyber Café" en clair, seulement dans le
    // snapshot Livewire encodé. On vérifie donc l'état du composant plutôt
    // que le HTML brut.
    $this->actingAs($user);
    Livewire::test('pages::admin.settings.edit')
        ->assertSet('name', 'Mon Cyber Café');
});

test('an admin can update the zone identity and colors', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test('pages::admin.settings.edit')
        ->set('name', 'Nouveau nom de zone')
        ->set('color_primary', '#123456')
        ->set('credential_mode', CredentialMode::Separate->value)
        ->call('save')
        ->assertHasNoErrors();

    $zone = WifiZoneSetting::current();
    expect($zone->name)->toBe('Nouveau nom de zone')
        ->and($zone->color_primary)->toBe('#123456')
        ->and($zone->credential_mode)->toBe(CredentialMode::Separate);
});

test('an admin can upload a new background image', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test('pages::admin.settings.edit')
        ->set('name', 'Zone Test')
        ->set('background', UploadedFile::fake()->image('fond.jpg'))
        ->call('save')
        ->assertHasNoErrors();

    $zone = WifiZoneSetting::current();
    expect($zone->background_path)->not->toBeNull();
    Storage::disk('public')->assertExists($zone->background_path);
});

test('updating settings requires a name', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test('pages::admin.settings.edit')
        ->set('name', '')
        ->call('save')
        ->assertHasErrors('name');
});
