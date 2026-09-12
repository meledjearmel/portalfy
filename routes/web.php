<?php

use App\Http\Controllers\GeniusPayWebhookController;
use Illuminate\Support\Facades\Route;

Route::livewire('/', 'pages::home')->name('home');

Route::livewire('/forfaits', 'pages::packages.index')->name('packages.index');
Route::livewire('/forfaits/{package}/commande', 'pages::orders.create')->name('orders.create');
Route::livewire('/commandes/{order:reference}/retour', 'pages::orders.return')->name('orders.return');
Route::livewire('/j-ai-un-code', 'pages::hotspot-access.show')->name('hotspot-access.show');

// Contrat webhook réel de l'API GeniusPay (voir GeniusPayWebhookController) :
// remplace la route "geniuspay/webhook" du SDK, qui vérifie un contrat obsolète.
// L'URL de webhook doit être mise à jour sur le dashboard marchand GeniusPay.
Route::post('/webhooks/geniuspay', GeniusPayWebhookController::class)->name('webhooks.geniuspay');

require __DIR__.'/settings.php';
require __DIR__.'/account.php';
require __DIR__.'/admin.php';
