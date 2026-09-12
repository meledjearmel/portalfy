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
