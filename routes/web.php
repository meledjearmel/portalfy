<?php

use Illuminate\Support\Facades\Route;

Route::livewire('/', 'pages::home')->name('home');

Route::livewire('/forfaits', 'pages::packages.index')->name('packages.index');
Route::livewire('/forfaits/{package}/commande', 'pages::orders.create')->name('orders.create');
Route::livewire('/j-ai-un-code', 'pages::hotspot-access.show')->name('hotspot-access.show');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('dashboard', 'dashboard')->name('dashboard');
});

require __DIR__.'/settings.php';
