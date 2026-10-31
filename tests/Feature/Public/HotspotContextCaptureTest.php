<?php

use App\Actions\CaptureHotspotContextAction;

test('a visitor arriving without hotspot query params captures no context', function () {
    $this->get(route('home'))->assertOk();

    expect(session(CaptureHotspotContextAction::SESSION_KEY))->toBeNull();
});

test('a visitor redirected by the captive portal captures a valid hotspot context in session', function () {
    $this->get(route('home', [
        'mac' => '00:1B:44:11:3A:B7',
        'ip' => '192.168.20.50',
        'link-login' => 'http://192.168.20.1/login',
        'link-orig' => 'http://example.com/',
    ]))->assertOk();

    expect(session(CaptureHotspotContextAction::SESSION_KEY))->toBe([
        'mac' => '00:1B:44:11:3A:B7',
        'ip' => '192.168.20.50',
        'link_login' => 'http://192.168.20.1/login',
        'link_orig' => 'http://example.com/',
    ]);
});

test('an invalid mac address is rejected and nothing is stored', function () {
    $this->get(route('home', [
        'mac' => 'not-a-mac',
        'ip' => '192.168.20.50',
        'link-login' => 'http://192.168.20.1/login',
    ]))->assertOk();

    expect(session(CaptureHotspotContextAction::SESSION_KEY))->toBeNull();
});

test('an invalid ip is rejected and nothing is stored', function () {
    $this->get(route('home', [
        'mac' => '00:1B:44:11:3A:B7',
        'ip' => 'not-an-ip',
        'link-login' => 'http://192.168.20.1/login',
    ]))->assertOk();

    expect(session(CaptureHotspotContextAction::SESSION_KEY))->toBeNull();
});

test('a link-login pointing to a public ip is rejected, preventing credential exfiltration', function () {
    $this->get(route('home', [
        'mac' => '00:1B:44:11:3A:B7',
        'ip' => '192.168.20.50',
        'link-login' => 'http://8.8.8.8/login',
    ]))->assertOk();

    expect(session(CaptureHotspotContextAction::SESSION_KEY))->toBeNull();
});

test('a link-login pointing to a public domain is rejected', function () {
    $this->get(route('home', [
        'mac' => '00:1B:44:11:3A:B7',
        'ip' => '192.168.20.50',
        'link-login' => 'http://malicious.example.com/login',
    ]))->assertOk();

    expect(session(CaptureHotspotContextAction::SESSION_KEY))->toBeNull();
});

test('a link-login using the local hotspot dns name of the router is accepted', function (string $linkLogin) {
    $this->get(route('home', [
        'mac' => 'E4:60:17:4F:7A:AE',
        'ip' => '10.20.0.249',
        'link-login' => $linkLogin,
        'link-orig' => 'http://www.msftconnecttest.com/redirect',
        'error' => '',
    ]))->assertOk();

    expect(session(CaptureHotspotContextAction::SESSION_KEY.'.link_login'))->toBe($linkLogin);
})->with([
    'http://hotspot.rapidnet.lan/login',
    'http://hotspot.local/login',
    'http://login/login',
]);

test('the hotspot context is captured on any portal page, not only the home page', function () {
    $this->get(route('packages.index', [
        'mac' => 'E4:60:17:4F:7A:AE',
        'ip' => '10.20.0.249',
        'link-login' => 'http://hotspot.rapidnet.lan/login',
    ]))->assertOk();

    expect(session(CaptureHotspotContextAction::SESSION_KEY.'.mac'))->toBe('E4:60:17:4F:7A:AE');
});

test('an invalid link-orig is dropped but does not prevent capturing the rest of the context', function () {
    $this->get(route('home', [
        'mac' => '00:1B:44:11:3A:B7',
        'ip' => '192.168.20.50',
        'link-login' => 'http://192.168.20.1/login',
        'link-orig' => 'not a url',
    ]))->assertOk();

    expect(session(CaptureHotspotContextAction::SESSION_KEY.'.link_orig'))->toBeNull();
});

test('a valid context already in session is not overwritten by a later navigation without hotspot params', function () {
    $this->withSession([
        CaptureHotspotContextAction::SESSION_KEY => [
            'mac' => '00:1B:44:11:3A:B7',
            'ip' => '192.168.20.50',
            'link_login' => 'http://192.168.20.1/login',
            'link_orig' => null,
        ],
    ])->get(route('packages.index'))->assertOk();

    $this->get(route('home'))->assertOk();

    expect(session(CaptureHotspotContextAction::SESSION_KEY.'.link_login'))
        ->toBe('http://192.168.20.1/login');
});
