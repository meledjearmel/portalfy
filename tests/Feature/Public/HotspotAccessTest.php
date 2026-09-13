<?php

use App\Enums\HotspotAccountStatus;
use App\Models\HotspotAccount;
use Livewire\Livewire;

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

test('with a hotspot context in session, a verified code shows a working connect-to-wifi form', function () {
    // hotspotContextSession() est définie dans HotspotAutoLoginTest.php,
    // chargée globalement avec le reste de la suite.
    $account = HotspotAccount::factory()->create(['status' => HotspotAccountStatus::Active]);

    $response = $this->withSession(hotspotContextSession())
        ->get(route('hotspot-access.show', ['code' => $account->code]));

    $response->assertOk();
    $response->assertSee('action="http://192.168.20.1/login"', false);
    $response->assertSee("name=\"username\" value=\"{$account->code}\"", false);
});

test('without a hotspot context in session, a verified code shows a manual message instead of a connect form', function () {
    $account = HotspotAccount::factory()->create(['status' => HotspotAccountStatus::Active]);

    $response = $this->get(route('hotspot-access.show', ['code' => $account->code]));

    $response->assertOk();
    $response->assertDontSee('hotspot-auto-login-form', false);
    $response->assertSee('Connecte-toi au wifi');
});
