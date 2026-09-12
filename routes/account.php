<?php

use App\Http\Controllers\DownloadInvoiceController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:customer'])->group(function () {
    Route::livewire('/compte', 'pages::account.profile')->name('account.profile');
    Route::livewire('/compte/achats', 'pages::account.orders')->name('account.orders');
    Route::livewire('/compte/factures', 'pages::account.invoices')->name('account.invoices');
    Route::livewire('/compte/acces/{hotspotAccount}', 'pages::account.access')->name('account.access.show');

    Route::get('/compte/factures/{invoice}/telecharger', DownloadInvoiceController::class)
        ->name('account.invoices.download');
});
