<?php

namespace App\Events;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AdminDashboardActivity implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Simple signal de rafraîchissement pour le tableau de bord admin (CA,
     * ventes, comptes actifs, activité récente) : canal privé, réservé au
     * guard "web" authentifié — sans risque d'auth "before-authorization"
     * ici, contrairement au parcours client anonyme (cf. HotspotAccountProvisioned).
     *
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('admin.dashboard'),
        ];
    }
}
