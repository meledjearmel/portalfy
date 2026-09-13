<?php

namespace App\Actions;

use App\Events\HotspotAccountProvisioned;
use App\Models\HotspotAccount;
use App\Models\Order;

class ProvisionHotspotAccountAction
{
    /**
     * Crée le compte Hotspot correspondant à une commande payée (voir
     * CreateHotspotAccountAction pour le détail du provisioning RouterOS) et
     * diffuse l'événement qui affiche le code en temps réel sur l'écran de
     * retour de paiement.
     */
    public function handle(Order $order): ?HotspotAccount
    {
        $account = (new CreateHotspotAccountAction)->handle($order->package, $order);

        if ($account) {
            HotspotAccountProvisioned::dispatch($account);
        }

        return $account;
    }
}
