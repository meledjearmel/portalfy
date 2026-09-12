<?php

test('the homepage shows no error banner without an error query param', function () {
    $response = $this->get(route('home'));

    $response->assertOk();
    $response->assertDontSee("Ce code n'est pas valide.");
});

test('the homepage reformulates a RouterOS login error into a simple message', function () {
    $response = $this->get(route('home', ['error' => 'invalid username or password']));

    $response->assertOk();
    $response->assertSee("Ce code n'est pas valide.");
    $response->assertDontSee('invalid username or password');
});

test('the homepage never leaks a raw, unrecognized RouterOS error message', function () {
    $response = $this->get(route('home', ['error' => 'RADIUS server is not responding']));

    $response->assertOk();
    $response->assertDontSee('RADIUS server is not responding');
    $response->assertSee('La connexion a échoué');
});
