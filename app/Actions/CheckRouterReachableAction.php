<?php

namespace App\Actions;

use App\Models\RouterSetting;
use Illuminate\Support\Facades\Log;
use ZillEAli\MikrotikLaravel\Facades\MikroTik;

/**
 * Vérifie, juste avant d'encaisser un client, que le routeur répond : sans
 * routeur joignable, le compte Hotspot ne pourrait pas être créé après le
 * paiement et le client paierait pour un accès qu'il ne reçoit pas.
 */
class CheckRouterReachableAction
{
    /**
     * Ne lève jamais d'exception : un routeur non configuré ou injoignable
     * renvoie simplement false.
     */
    public function handle(): bool
    {
        if (! RouterSetting::current()->isConfigured()) {
            return false;
        }

        try {
            MikroTik::system()->getIdentity();
        } catch (\Throwable $e) {
            Log::warning('RouterOS: routeur injoignable avant un paiement', [
                'error' => $e->getMessage(),
            ]);

            return false;
        }

        return true;
    }
}
