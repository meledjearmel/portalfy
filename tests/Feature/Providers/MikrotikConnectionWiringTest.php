<?php

use App\Models\RouterSetting;
use ZillEAli\MikrotikLaravel\MikrotikManager;

test('the container builds the mikrotik connection from the persisted router settings', function () {
    RouterSetting::current()->update([
        'host' => '10.20.30.40',
        'port' => 8729,
        'username' => 'super-admin',
        'password' => 'secret',
        'timeout' => 15,
        'use_ssl' => true,
    ]);

    // Résoudre MikrotikManager n'ouvre aucune connexion (le socket n'est créé
    // que par un appel à un manager, ex. ->system()) : on peut donc vérifier
    // ici la config effectivement injectée par AppServiceProvider sans jamais
    // toucher le réseau, ce qu'imposerait un mock du client RouterOS sinon.
    // `$config` est protégé et le package ne fournit pas d'accesseur public.
    $manager = app(MikrotikManager::class);
    $config = Closure::bind(fn () => $this->config, $manager, MikrotikManager::class)();

    expect($config['host'])->toBe('10.20.30.40')
        ->and($config['port'])->toBe(8729)
        ->and($config['username'])->toBe('super-admin')
        ->and($config['password'])->toBe('secret')
        ->and($config['timeout'])->toBe(15)
        ->and($config['ssl'])->toBeTrue();
});
