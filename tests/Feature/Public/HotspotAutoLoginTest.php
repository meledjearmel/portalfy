<?php

use App\Actions\CaptureHotspotContextAction;
use App\Enums\CredentialMode;
use App\Models\HotspotAccount;
use App\Models\Order;
use App\Models\WifiZoneSetting;

function hotspotContextSession(?string $linkOrig = null): array
{
    return [
        CaptureHotspotContextAction::SESSION_KEY => [
            'mac' => '00:1B:44:11:3A:B7',
            'ip' => '192.168.20.50',
            'link_login' => 'http://192.168.20.1/login',
            'link_orig' => $linkOrig,
        ],
    ];
}

test('without a hotspot context in session, the code is shown with a manual reconnection message instead of an auto-login attempt', function () {
    $order = Order::factory()->paid()->create();
    $account = HotspotAccount::factory()->create(['order_id' => $order->id]);
    $zone = WifiZoneSetting::current();

    $response = $this->get(route('orders.return', $order));

    $response->assertOk();
    $response->assertSee($account->code);
    $response->assertSee("Connecte-toi au wifi {$zone->name}", false);
    $response->assertDontSee('hotspot-auto-login-form', false);
});

test('with a valid hotspot context in session, an auto-login form posts the unique code to link-login', function () {
    $order = Order::factory()->paid()->create();
    $account = HotspotAccount::factory()->create(['order_id' => $order->id, 'secret' => null]);
    WifiZoneSetting::current()->update(['credential_mode' => CredentialMode::Unique]);

    $response = $this->withSession(hotspotContextSession())->get(route('orders.return', $order));

    $response->assertOk();
    $response->assertSee('action="http://192.168.20.1/login"', false);
    $response->assertSee("name=\"username\" value=\"{$account->code}\"", false);
    $response->assertSee("name=\"password\" value=\"{$account->code}\"", false);
    $response->assertSee('document.getElementById(\'hotspot-auto-login-form\').submit()', false);
});

test('in separate credential mode, the auto-login form posts the code as username and the secret as password', function () {
    $order = Order::factory()->paid()->create();
    WifiZoneSetting::current()->update(['credential_mode' => CredentialMode::Separate]);
    $account = HotspotAccount::factory()->create(['order_id' => $order->id, 'secret' => 'SECRET1']);

    $response = $this->withSession(hotspotContextSession())->get(route('orders.return', $order));

    $response->assertOk();
    $response->assertSee("name=\"username\" value=\"{$account->code}\"", false);
    $response->assertSee('name="password" value="SECRET1"', false);
});

test('link-orig is submitted as the dst field when present', function () {
    $order = Order::factory()->paid()->create();
    HotspotAccount::factory()->create(['order_id' => $order->id]);

    $response = $this->withSession(hotspotContextSession('http://example.com/original-page'))
        ->get(route('orders.return', $order));

    $response->assertOk();
    $response->assertSee('name="dst" value="http://example.com/original-page"', false);
});

test('without link-orig, no dst field is submitted', function () {
    $order = Order::factory()->paid()->create();
    HotspotAccount::factory()->create(['order_id' => $order->id]);

    $response = $this->withSession(hotspotContextSession())->get(route('orders.return', $order));

    $response->assertOk();
    $response->assertDontSee('name="dst"', false);
});

test('a valid hotspot context does not trigger an auto-login attempt before the hotspot account is provisioned', function () {
    $order = Order::factory()->paid()->create();

    $response = $this->withSession(hotspotContextSession())->get(route('orders.return', $order));

    $response->assertOk();
    $response->assertSee('en cours d');
    $response->assertDontSee('hotspot-auto-login-form', false);
});
