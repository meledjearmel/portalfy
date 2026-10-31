<?php

namespace App\Actions;

use App\Console\Commands\SyncHotspotAccounts;
use App\Models\HotspotAccount;
use Illuminate\Support\Facades\Log;
use ZillEAli\MikrotikLaravel\Facades\MikroTik;

/**
 * Le routeur, pas la base de Portalfy, décide si un code donne accès au
 * WiFi : un code connu ici peut avoir été supprimé, désactivé ou épuisé sur
 * RouterOS. On l'interroge donc avant d'annoncer un code comme valide.
 */
class CheckCodeOnRouterAction
{
    /**
     * Ne lève jamais d'exception : renvoie null si le routeur accepte le
     * code, sinon le message à afficher au visiteur.
     */
    public function handle(HotspotAccount $account): ?string
    {
        try {
            $routerUser = MikroTik::hotspot()->getUser($account->code);
        } catch (\Throwable $e) {
            Log::warning('RouterOS: vérification d\'un code impossible', [
                'hotspot_account_id' => $account->id,
                'error' => $e->getMessage(),
            ]);

            return 'Le réseau WiFi ne répond pas pour le moment. Réessayez dans un instant.';
        }

        if ($routerUser === null) {
            return "Ce code n'est pas valide.";
        }

        if (($routerUser['disabled'] ?? 'false') === 'true') {
            return 'Cet accès a été suspendu.';
        }

        $limitSeconds = SyncHotspotAccounts::parseUptime($routerUser['limit-uptime'] ?? '');
        $uptimeSeconds = SyncHotspotAccounts::parseUptime($routerUser['uptime'] ?? '0s');

        if ($limitSeconds > 0 && $uptimeSeconds >= $limitSeconds) {
            return 'Le temps de ce code est épuisé.';
        }

        return null;
    }
}
