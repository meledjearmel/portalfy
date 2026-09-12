<?php

use App\Livewire\Actions\Logout;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::livewire('/admin/login', 'pages::admin.auth.login')->name('admin.login');
});

Route::prefix('admin')->middleware(['auth:web'])->name('admin.')->group(function () {
    Route::redirect('/', '/admin/tableau-de-bord');

    Route::livewire('/tableau-de-bord', 'pages::admin.dashboard')->name('dashboard');
    Route::livewire('/commandes', 'pages::admin.orders.index')->name('orders.index');
    Route::livewire('/comptes-hotspot', 'pages::admin.hotspot-accounts.index')->name('hotspot-accounts.index');
    Route::livewire('/forfaits', 'pages::admin.packages.index')->name('packages.index');
    Route::livewire('/clients', 'pages::admin.customers.index')->name('customers.index');
    Route::livewire('/corbeille', 'pages::admin.trash.index')->name('trash.index');
    Route::livewire('/parametres', 'pages::admin.settings.edit')->name('settings.edit');

    Route::post('/logout', Logout::class)->name('logout');
});
