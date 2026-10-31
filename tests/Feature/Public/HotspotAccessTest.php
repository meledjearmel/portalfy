<?php

use App\Enums\HotspotAccountStatus;
use App\Models\HotspotAccount;
use Livewire\Livewire;
use ZillEAli\MikrotikLaravel\Exceptions\ApiException;
use ZillEAli\MikrotikLaravel\Facades\MikroTik;
use ZillEAli\MikrotikLaravel\Services\HotspotManager;
use ZillEAli\MikrotikLaravel\Testing\MikrotikFake;

// Par défaut, le routeur connaît le code et lui laisse du temps : le
// FakeRouterosClient ignore le filtre par nom et renvoie cette ligne.
beforeEach(fn () => MikrotikFake::fake([
    '/ip/hotspot/user/print' => [['.id' => '*1', 'name' => 'ANY', 'disabled' => 'false', 'uptime' => '0s', 'limit-uptime' => '1h']],
]));

function routerReturnsUser(?array $routerUser): void
{
    MikrotikFake::fake(['/ip/hotspot/user/print' => $routerUser ? [$routerUser] : []]);
}

test('a code known here but deleted on the router is rejected', function () {
    $account = HotspotAccount::factory()->voucher()->create();
    routerReturnsUser(null);

    Livewire::test('pages::hotspot-access.show')
        ->set('code', $account->code)
        ->call('verify')
        ->assertHasErrors(['code' => "Ce code n'est pas valide."]);
});

test('a code disabled on the router is rejected', function () {
    $account = HotspotAccount::factory()->voucher()->create();
    routerReturnsUser(['name' => $account->code, 'disabled' => 'true']);

    Livewire::test('pages::hotspot-access.show')
        ->set('code', $account->code)
        ->call('verify')
        ->assertHasErrors(['code' => 'Cet accès a été suspendu.']);
});

test('a code whose time is exhausted on the router is rejected', function () {
    $account = HotspotAccount::factory()->voucher()->create();
    routerReturnsUser(['name' => $account->code, 'uptime' => '1h', 'limit-uptime' => '1h']);

    Livewire::test('pages::hotspot-access.show')
        ->set('code', $account->code)
        ->call('verify')
        ->assertHasErrors(['code' => 'Le temps de ce code est épuisé.']);

    expect($account->fresh()->activated_at)->toBeNull();
});

test('a code is not accepted while the router cannot be reached', function () {
    $account = HotspotAccount::factory()->voucher()->create();
    $hotspot = Mockery::mock(HotspotManager::class);
    $hotspot->shouldReceive('getUser')->andThrow(new ApiException('injoignable'));
    $manager = Mockery::mock();
    $manager->shouldReceive('hotspot')->andReturn($hotspot);
    MikroTik::swap($manager);

    Livewire::test('pages::hotspot-access.show')
        ->set('code', $account->code)
        ->call('verify')
        ->assertHasErrors(['code' => 'Le réseau WiFi ne répond pas pour le moment. Réessayez dans un instant.']);
});

test('a code that is not 6 characters long fails validation', function () {
    Livewire::test('pages::hotspot-access.show')
        ->set('code', 'ABC')
        ->call('verify')
        ->assertHasErrors(['code' => 'size']);
});

test('an unknown code is rejected as invalid', function () {
    Livewire::test('pages::hotspot-access.show')
        ->set('code', 'ZZZZZZ')
        ->call('verify')
        ->assertHasErrors(['code' => "Ce code n'est pas valide."]);
});

test('an already used code is rejected with a clear message', function () {
    $account = HotspotAccount::factory()->used()->create();

    Livewire::test('pages::hotspot-access.show')
        ->set('code', $account->code)
        ->call('verify')
        ->assertHasErrors(['code' => 'Ce code a déjà été utilisé.']);
});

test('an expired code is rejected with a clear message', function () {
    $account = HotspotAccount::factory()->expired()->create();

    Livewire::test('pages::hotspot-access.show')
        ->set('code', $account->code)
        ->call('verify')
        ->assertHasErrors(['code' => 'Ce code a expiré.']);
});

test('a suspended code is rejected with a clear message', function () {
    $account = HotspotAccount::factory()->suspended()->create();

    Livewire::test('pages::hotspot-access.show')
        ->set('code', $account->code)
        ->call('verify')
        ->assertHasErrors(['code' => 'Cet accès a été suspendu.']);
});

test('an active code is accepted and shown to the customer', function () {
    $account = HotspotAccount::factory()->create(['status' => HotspotAccountStatus::Active]);

    Livewire::test('pages::hotspot-access.show')
        ->set('code', $account->code)
        ->call('verify')
        ->assertHasNoErrors()
        ->assertSee($account->code);
});

test('a code typed in lowercase is still accepted', function () {
    $account = HotspotAccount::factory()->create([
        'status' => HotspotAccountStatus::Active,
        'code' => 'ABC123',
    ]);

    Livewire::test('pages::hotspot-access.show')
        ->set('code', 'abc123')
        ->call('verify')
        ->assertHasNoErrors()
        ->assertSee($account->code);
});

test('a code shared via link is pre-filled and verified automatically, without retyping it', function () {
    $account = HotspotAccount::factory()->create([
        'status' => HotspotAccountStatus::Active,
        'code' => 'ABC123',
    ]);

    $response = $this->get(route('hotspot-access.show', ['code' => strtolower($account->code)]));

    $response->assertOk();
    $response->assertSee($account->code);
});

test('an invalid code shared via link shows the same error as a manually typed one', function () {
    $response = $this->get(route('hotspot-access.show', ['code' => 'ZZZZZZ']));

    $response->assertOk();
    $response->assertSee("Ce code n'est pas valide.");
});

test('with a hotspot context in session, a verified code redirects to the router login with the code', function () {
    // hotspotContextSession() est définie dans HotspotAutoLoginTest.php,
    // chargée globalement avec le reste de la suite.
    $account = HotspotAccount::factory()->create(['status' => HotspotAccountStatus::Active]);

    $response = $this->withSession(hotspotContextSession())
        ->get(route('hotspot-access.show', ['code' => $account->code]));

    $response->assertOk();
    $response->assertSee('data-hotspot-auto-login', false);
    $response->assertSee("href=\"http://192.168.20.1/login?username={$account->code}&amp;password={$account->code}\"", false);
});

test('without a hotspot context in session, a verified code shows a manual message instead of a connect form', function () {
    $account = HotspotAccount::factory()->create(['status' => HotspotAccountStatus::Active]);

    $response = $this->get(route('hotspot-access.show', ['code' => $account->code]));

    $response->assertOk();
    $response->assertDontSee('data-hotspot-auto-login', false);
    $response->assertSee('Connecte-toi au wifi');
});

test('an admin-created code is marked as started at its first connection, without any end date', function () {
    $this->travelTo(now()->startOfMinute());
    $account = HotspotAccount::factory()->voucher()->create();

    $this->travel(3)->days();

    Livewire::test('pages::hotspot-access.show')
        ->set('code', $account->code)
        ->call('verify')
        ->assertHasNoErrors();

    $account->refresh();

    expect($account->activated_at->toDateTimeString())->toBe(now()->toDateTimeString())
        ->and($account->expires_at)->toBeNull();
});

test('validating a code again keeps its first connection date', function () {
    $account = HotspotAccount::factory()->voucher()->create();

    Livewire::test('pages::hotspot-access.show')->set('code', $account->code)->call('verify');
    $firstConnection = $account->fresh()->activated_at;

    $this->travel(10)->minutes();

    Livewire::test('pages::hotspot-access.show')->set('code', $account->code)->call('verify');

    expect($account->fresh()->activated_at->toDateTimeString())->toBe($firstConnection->toDateTimeString());
});
