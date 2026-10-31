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
    $response->assertDontSee('data-hotspot-auto-login', false);
});

test('with a valid hotspot context in session, the visitor is redirected to link-login with the unique code', function () {
    $order = Order::factory()->paid()->create();
    $account = HotspotAccount::factory()->create(['order_id' => $order->id, 'secret' => null]);
    WifiZoneSetting::current()->update(['credential_mode' => CredentialMode::Unique]);

    $response = $this->withSession(hotspotContextSession())->get(route('orders.return', $order));

    $response->assertOk();
    $response->assertSee('data-hotspot-auto-login', false);
    $response->assertSee("href=\"http://192.168.20.1/login?username={$account->code}&amp;password={$account->code}\"", false);
});

test('in separate credential mode, the code is sent as username and the secret as password', function () {
    $order = Order::factory()->paid()->create();
    WifiZoneSetting::current()->update(['credential_mode' => CredentialMode::Separate]);
    $account = HotspotAccount::factory()->create(['order_id' => $order->id, 'secret' => 'SECRET1']);

    $response = $this->withSession(hotspotContextSession())->get(route('orders.return', $order));

    $response->assertOk();
    $response->assertSee("href=\"http://192.168.20.1/login?username={$account->code}&amp;password=SECRET1\"", false);
});

test('link-orig is sent as the dst parameter when present', function () {
    $order = Order::factory()->paid()->create();
    HotspotAccount::factory()->create(['order_id' => $order->id]);

    $response = $this->withSession(hotspotContextSession('http://example.com/original-page'))
        ->get(route('orders.return', $order));

    $response->assertOk();
    $response->assertSee('&amp;dst=http%3A%2F%2Fexample.com%2Foriginal-page"', false);
});

test('without link-orig, no dst parameter is sent', function () {
    $order = Order::factory()->paid()->create();
    HotspotAccount::factory()->create(['order_id' => $order->id]);

    $response = $this->withSession(hotspotContextSession())->get(route('orders.return', $order));

    $response->assertOk();
    $response->assertDontSee('dst=', false);
});

test('a valid hotspot context does not trigger an auto-login attempt before the hotspot account is provisioned', function () {
    $order = Order::factory()->paid()->create();

    $response = $this->withSession(hotspotContextSession())->get(route('orders.return', $order));

    $response->assertOk();
    $response->assertSee('en cours d');
    $response->assertDontSee('data-hotspot-auto-login', false);
});
