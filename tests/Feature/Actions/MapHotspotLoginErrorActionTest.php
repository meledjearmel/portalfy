<?php

use App\Actions\MapHotspotLoginErrorAction;

test('it maps uptime and traffic limit errors to the expired message', function (string $routerMessage) {
    $message = (new MapHotspotLoginErrorAction)->handle($routerMessage);

    expect($message)->toBe('Ce code a expiré.');
})->with([
    'user JOHN123 has reached uptime limit',
    'user JOHN123 has reached traffic limit',
]);

test('it maps the session limit error to the already-used message', function () {
    $message = (new MapHotspotLoginErrorAction)->handle('no more sessions are allowed for user JOHN123');

    expect($message)->toBe('Ce code a déjà été utilisé.');
});

test('it maps invalid credential and mac errors to the invalid code message', function (string $routerMessage) {
    $message = (new MapHotspotLoginErrorAction)->handle($routerMessage);

    expect($message)->toBe("Ce code n'est pas valide.");
})->with([
    'invalid username or password',
    'invalid username (JOHN123): this MAC address is not yours',
    'user JOHN123 is not allowed to log in from this MAC address',
]);

test('it maps a concurrent authorization attempt to a retry message', function () {
    $message = (new MapHotspotLoginErrorAction)->handle('already authorizing, retry later');

    expect($message)->toBe('Veuillez patienter quelques secondes puis réessayer.');
});

test('it falls back to a generic message for unrecognized RouterOS errors, never echoing the raw text', function (string $routerMessage) {
    $message = (new MapHotspotLoginErrorAction)->handle($routerMessage);

    expect($message)->toBe('La connexion a échoué. Réessayez, ou connectez-vous manuellement avec votre code.')
        ->and($message)->not->toContain($routerMessage);
})->with([
    'RADIUS server is not responding',
    'hotspot service is shutting down',
    'internal error (something obscure)',
]);

test('the matching is case-insensitive', function () {
    $message = (new MapHotspotLoginErrorAction)->handle('INVALID USERNAME OR PASSWORD');

    expect($message)->toBe("Ce code n'est pas valide.");
});
