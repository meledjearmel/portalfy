<?php

use App\Models\RouterSetting;
use App\Models\User;
use Livewire\Livewire;
use ZillEAli\MikrotikLaravel\Exceptions\ApiException;
use ZillEAli\MikrotikLaravel\Facades\MikroTik;
use ZillEAli\MikrotikLaravel\Services\HotspotManager;
use ZillEAli\MikrotikLaravel\Services\InterfaceManager;
use ZillEAli\MikrotikLaravel\Services\SystemManager;
use ZillEAli\MikrotikLaravel\Testing\MikrotikFake;

// Réponses minimales couvrant les quatre appels faits par with() : identité,
// ressources système, interfaces, sessions Hotspot actives.
function fakeReachableRouter(): MikrotikFake
{
    return MikrotikFake::fake([
        '/system/identity/print' => [['name' => 'MonRouteur']],
        '/system/resource/print' => [[
            'version' => '7.14.3',
            'uptime' => '2h15m',
            'board-name' => 'hAP ac2',
            'cpu-load' => '12',
            'free-memory' => '50000000',
            'total-memory' => '256000000',
        ]],
        '/interface/print' => [
            ['.id' => '*1', 'name' => 'ether1', 'type' => 'ether', 'running' => 'true', 'disabled' => 'false', 'mac-address' => '11:22:33:44:55:66'],
        ],
        '/ip/hotspot/active/print' => [
            ['.id' => '*A1', 'user' => 'ABC123', 'address' => '192.168.88.50', 'mac-address' => 'AA:BB:CC:DD:EE:FF', 'uptime' => '5m'],
        ],
    ]);
}

test('guests are redirected to the admin login screen', function () {
    $response = $this->get(route('admin.router.index'));

    $response->assertRedirect(route('admin.login'));
});

test('an admin sees the router status, its interfaces and its active hotspot sessions', function () {
    fakeReachableRouter();
    RouterSetting::factory()->create();

    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('admin.router.index'));

    $response->assertOk();
    $response->assertSee('MonRouteur');
    $response->assertSee('7.14.3');
    $response->assertSee('ether1');
    $response->assertSee('ABC123');
    $response->assertSee('192.168.88.50');
});

test('an admin can disconnect an active hotspot session from the router view', function () {
    $fake = fakeReachableRouter();
    RouterSetting::factory()->create();

    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test('pages::admin.router.index')
        ->call('kickSession', 'ABC123');

    $fake->assertQueried('/ip/hotspot/active/remove');
});

test('disconnecting a session reports failure without throwing when the router is unreachable', function () {
    // Le mock couvre les quatre appels faits par with() (system, interfaces,
    // hotspot) : MikrotikFake ne peut pas être combiné à MikroTik::shouldReceive()
    // dans le même test, la classe finale MikrotikFake ne peut pas être mockée
    // une fois déjà bindée dans le conteneur.
    $systemManager = Mockery::mock(SystemManager::class);
    $systemManager->shouldReceive('getIdentity')->andReturn('MonRouteur');
    $systemManager->shouldReceive('getResources')->andReturn([]);

    $interfaceManager = Mockery::mock(InterfaceManager::class);
    $interfaceManager->shouldReceive('getInterfaces')->andReturn([]);

    $hotspotManager = Mockery::mock(HotspotManager::class);
    $hotspotManager->shouldReceive('getActiveHosts')->andReturn([]);
    $hotspotManager->shouldReceive('kickHost')->andThrow(new ApiException('Router unreachable'));

    MikroTik::shouldReceive('system')->andReturn($systemManager);
    MikroTik::shouldReceive('interfaces')->andReturn($interfaceManager);
    MikroTik::shouldReceive('hotspot')->andReturn($hotspotManager);

    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test('pages::admin.router.index')
        ->call('kickSession', 'ABC123');
})->throwsNoExceptions();

test('an admin opening the configuration form sees the current router settings prefilled', function () {
    fakeReachableRouter();

    RouterSetting::factory()->create([
        'host' => '10.10.10.1',
        'port' => 8729,
        'username' => 'super-admin',
        'password' => 'secret',
        'timeout' => 20,
        'use_ssl' => true,
    ]);

    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test('pages::admin.router.index')
        ->call('configure')
        ->assertSet('host', '10.10.10.1')
        ->assertSet('port', 8729)
        ->assertSet('username', 'super-admin')
        ->assertSet('password', 'secret')
        ->assertSet('timeout', 20)
        ->assertSet('use_ssl', true);
});

test('an admin can save new router connection settings', function () {
    fakeReachableRouter();

    RouterSetting::factory()->create(['host' => '192.168.88.1']);

    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test('pages::admin.router.index')
        ->set('host', '10.0.0.5')
        ->set('port', 8729)
        ->set('username', 'super-admin')
        ->set('password', 'nouveau-secret')
        ->set('timeout', 15)
        ->set('use_ssl', true)
        ->call('saveSettings')
        ->assertHasNoErrors();

    $setting = RouterSetting::current();
    expect($setting->host)->toBe('10.0.0.5')
        ->and($setting->port)->toBe(8729)
        ->and($setting->username)->toBe('super-admin')
        ->and($setting->password)->toBe('nouveau-secret')
        ->and($setting->timeout)->toBe(15)
        ->and($setting->use_ssl)->toBeTrue();
});

test('saving router settings requires a host and a username', function () {
    fakeReachableRouter();

    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test('pages::admin.router.index')
        ->set('host', '')
        ->set('username', '')
        ->call('saveSettings')
        ->assertHasErrors(['host', 'username']);
});

test('testing the connection requires a host and a username, without attempting to connect', function () {
    fakeReachableRouter();

    $user = User::factory()->create();
    $this->actingAs($user);

    Livewire::test('pages::admin.router.index')
        ->set('host', '')
        ->set('username', '')
        ->call('testConnection')
        ->assertHasErrors(['host', 'username']);

    // testConnection() ne fait qu'essayer une connexion jetable : elle ne lit
    // ni n'écrit jamais RouterSetting, contrairement à saveSettings() — la
    // ligne singleton reste donc vide (simplement créée par with() au montage).
    expect(RouterSetting::current()->isConfigured())->toBeFalse();
});

test('an unreachable router shows an offline notice instead of crashing the page', function () {
    RouterSetting::factory()->create();

    MikroTik::shouldReceive('system')->andReturnUsing(function () {
        $manager = Mockery::mock(SystemManager::class);
        $manager->shouldReceive('getIdentity')->andThrow(new ApiException('Router unreachable'));

        return $manager;
    });

    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('admin.router.index'));

    $response->assertOk();
    $response->assertSee('Routeur injoignable');
});
