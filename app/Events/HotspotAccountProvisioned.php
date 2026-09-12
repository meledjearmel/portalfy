<?php

namespace App\Events;

use App\Models\HotspotAccount;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class HotspotAccountProvisioned implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public HotspotAccount $hotspotAccount,
    ) {}

    /**
     * Canal public (pas d'authentification) : le parcours est anonyme par
     * design (achat sans compte). Un canal privé Reverb/Pusher exige un
     * utilisateur authentifié avant même d'exécuter l'autorisation
     * personnalisée — inutilisable ici. La référence de la commande (10
     * caractères aléatoires, ~3,6×10¹⁵ combinaisons) sert de secret : le nom
     * du canal lui-même n'est jamais deviné par un tiers.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('orders.'.$this->hotspotAccount->order->reference),
        ];
    }

    /**
     * Ne jamais exposer $secret (mot de passe RouterOS en mode "separate") :
     * seul le code affiché au client transite sur le channel.
     *
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'code' => $this->hotspotAccount->code,
            'expiresAt' => $this->hotspotAccount->expires_at?->toIso8601String(),
        ];
    }
}
