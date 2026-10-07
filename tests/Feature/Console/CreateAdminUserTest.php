<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

test('it creates a verified user from options', function () {
    $this->artisan('app:create-user', [
        '--name' => 'Jane Admin',
        '--email' => 'jane@portalfy.test',
        '--password' => 'Secret-Password-123',
    ])->assertSuccessful();

    $user = User::query()->where('email', 'jane@portalfy.test')->firstOrFail();

    expect($user->name)->toBe('Jane Admin')
        ->and($user->email_verified_at)->not->toBeNull()
        ->and(Hash::check('Secret-Password-123', $user->password))->toBeTrue();
});

test('it refuses a duplicate email', function () {
    User::factory()->create(['email' => 'jane@portalfy.test']);

    $this->artisan('app:create-user', [
        '--name' => 'Jane Admin',
        '--email' => 'jane@portalfy.test',
        '--password' => 'Secret-Password-123',
    ])->assertFailed();

    expect(User::query()->where('email', 'jane@portalfy.test')->count())->toBe(1);
});
