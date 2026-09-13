<?php

use App\Models\Customer;
use App\Models\HotspotAccount;
use App\Models\Order;
use App\Models\Package;
use App\Models\RouterSetting;
use App\Models\User;
use ZillEAli\MikrotikLaravel\Exceptions\ApiException;
use ZillEAli\MikrotikLaravel\Facades\MikroTik;
use ZillEAli\MikrotikLaravel\Services\HotspotManager;
use ZillEAli\MikrotikLaravel\Testing\MikrotikFake;

// Le tableau de bord est derrière EnsureRouterIsConfigured : sans routeur
// enregistré, un admin authentifié serait redirigé vers l'écran Routeur
// avant même d'atteindre cette page.
beforeEach(fn () => RouterSetting::factory()->create());

// Le nombre de sessions actives interroge réellement RouterOS à chaque
// affichage : sans fake, ces tests tenteraient une vraie connexion socket
// vers l'hôte bidon de la factory. Appelé explicitement dans chaque test (pas
// dans beforeEach) car un seul test simule plutôt un échec via
// MikroTik::shouldReceive(), incompatible avec MikrotikFake déjà posé —
// voir RouterIndexTest pour la même contrainte.
function fakeRouterForDashboard(): MikrotikFake
{
    return MikrotikFake::fake();
}

test('guests are redirected to the admin login screen', function () {
    $response = $this->get(route('admin.dashboard'));

    $response->assertRedirect(route('admin.login'));
});

test('an authenticated admin sees revenue, sales count and active accounts', function () {
    fakeRouterForDashboard();
    $user = User::factory()->create();

    $paidOrder = Order::factory()->paid()->create(['amount' => 1000]);
    Order::factory()->paid()->create(['amount' => 2000]);
    Order::factory()->create(); // pending, excluded from revenue/sales

    // Rattachée à une commande déjà comptée ci-dessus : la factory
    // HotspotAccount crée sinon sa propre commande payée avec un montant
    // aléatoire, faussant le total attendu.
    HotspotAccount::factory()->create(['order_id' => $paidOrder->id]);

    $response = $this->actingAs($user)->get(route('admin.dashboard'));

    $response->assertOk();
    $response->assertSee('3 000 F');
    $response->assertSee('Ventes');
});

test('recent orders are listed with a readable status', function () {
    fakeRouterForDashboard();
    $user = User::factory()->create();
    $order = Order::factory()->paid()->create();

    $response = $this->actingAs($user)->get(route('admin.dashboard'));

    $response->assertOk();
    $response->assertSee($order->package->name);
    $response->assertSee('Payée');
});

test('active customers and active vouchers are counted separately from purchased accounts', function () {
    fakeRouterForDashboard();
    $user = User::factory()->create();

    Customer::factory()->create();
    Customer::factory()->suspended()->create();

    HotspotAccount::factory()->voucher()->create(); // actif par défaut
    HotspotAccount::factory()->create(); // achat, ne doit pas compter comme voucher

    $response = $this->actingAs($user)->get(route('admin.dashboard'));

    $response->assertOk();
    $response->assertSee('Clients actifs');
    $response->assertSee('Vouchers actifs');
});

test('the active sessions count reflects the router when it is reachable', function () {
    MikrotikFake::fake(['/ip/hotspot/active/print' => [
        ['.id' => '*1', 'user' => 'ABC123'],
        ['.id' => '*2', 'user' => 'DEF456'],
    ]]);

    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('admin.dashboard'));

    $response->assertOk();
    $response->assertSee('Sessions actives');
    $response->assertDontSee('Routeur injoignable');
});

test('the active sessions card shows an offline notice instead of a fake zero when the router is unreachable', function () {
    MikroTik::shouldReceive('hotspot')->andReturnUsing(function () {
        $manager = Mockery::mock(HotspotManager::class);
        $manager->shouldReceive('getActiveHosts')->andThrow(new ApiException('Router unreachable'));

        return $manager;
    });

    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('admin.dashboard'));

    $response->assertOk();
    $response->assertSee('Routeur injoignable');
});

test('the last 7 days of paid revenue are shown, including zero-sale days', function () {
    fakeRouterForDashboard();
    $user = User::factory()->create();

    Order::factory()->paid()->create(['amount' => 1500, 'created_at' => now()]);
    Order::factory()->paid()->create(['amount' => 2500, 'created_at' => now()->subDays(3)]);
    // Hors fenêtre des 7 jours : ne doit pas apparaître dans le total du jour.
    Order::factory()->paid()->create(['amount' => 9999, 'created_at' => now()->subDays(10)]);

    $response = $this->actingAs($user)->get(route('admin.dashboard'));

    $response->assertOk();
    $response->assertSee('Ventes des 7 derniers jours');
    $response->assertSee('1 500');
    $response->assertSee('2 500');
    $response->assertDontSee('9 999');
});

test('orders are broken down by status with their real count', function () {
    fakeRouterForDashboard();
    $user = User::factory()->create();

    Order::factory()->paid()->count(2)->create();
    Order::factory()->failed()->create();

    $response = $this->actingAs($user)->get(route('admin.dashboard'));

    $response->assertOk();
    $response->assertSee('Commandes par statut');
    $response->assertSee('Échouée');
});

test('the best-selling packages are ranked by their paid orders, excluding packages with no sales', function () {
    fakeRouterForDashboard();
    $user = User::factory()->create();

    $bestSeller = Package::factory()->create(['name' => 'Forfait Populaire']);
    $neverSold = Package::factory()->create(['name' => 'Forfait Jamais Vendu']);

    Order::factory()->paid()->count(3)->create(['package_id' => $bestSeller->id]);

    $response = $this->actingAs($user)->get(route('admin.dashboard'));

    $response->assertOk();
    $response->assertSee('Forfaits les plus vendus');
    $response->assertSee('Forfait Populaire');
    $response->assertDontSee('Forfait Jamais Vendu');
});
