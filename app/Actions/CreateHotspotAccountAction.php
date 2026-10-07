<?php

namespace App\Actions;

use App\Enums\CredentialMode;
use App\Enums\HotspotAccountStatus;
use App\Models\HotspotAccount;
use App\Models\Order;
use App\Models\Package;
use App\Models\WifiZoneSetting;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use ZillEAli\MikrotikLaravel\Facades\MikroTik;

/**
 * Crée un compte Hotspot pour un forfait donné : soit rattaché à une
 * commande payée (ProvisionHotspotAccountAction), soit comme voucher généré
 * directement par l'admin ($order = null, voir la gestion des comptes
 * Hotspot dans l'administration).
 */
class CreateHotspotAccountAction
{
    /**
     * Mémoïsé par instance : une génération de plusieurs vouchers pour le
     * même forfait ne doit interroger/créer le profil RouterOS qu'une seule
     * fois, pas à chaque compte créé.
     *
     * @var array<int, string>
     */
    private array $ensuredProfiles = [];

    /**
     * Ne lève jamais d'exception : voir ProvisionHotspotAccountAction pour
     * le détail de ce contrat côté commande, qui s'applique de la même façon
     * à un voucher admin (on ne veut pas qu'un routeur injoignable fasse
     * planter une génération en lot).
     */
    public function handle(Package $package, ?Order $order = null, ?string $comment = null): ?HotspotAccount
    {
        $zone = WifiZoneSetting::current();

        $code = Str::upper(Str::random(6));
        $secret = $zone->credential_mode === CredentialMode::Separate
            ? Str::upper(Str::random(6))
            : null;

        $data = [
            'name' => $code,
            'password' => $secret ?? $code,
            'comment' => $comment ?? ($order ? "Commande {$order->reference}" : 'Voucher admin'),
            'limit-uptime' => ($package->duration_minutes * 60).'s',
        ];

        $isCreatedOnRouter = false;

        try {
            if ($package->max_speed_mbps) {
                $data['profile'] = $this->ensureSpeedProfile($package->max_speed_mbps);
            }

            MikroTik::hotspot()->createUser($data);
            $isCreatedOnRouter = true;

            $account = HotspotAccount::create([
                'order_id' => $order?->id,
                'package_id' => $package->id,
                'code' => $code,
                'secret' => $secret,
                'status' => HotspotAccountStatus::Active,
                'activated_at' => now(),
                'expires_at' => now()->addMinutes($package->duration_minutes),
            ]);
        } catch (\Throwable $e) {
            Log::error('RouterOS: échec de la création du compte Hotspot', [
                'order_id' => $order?->id,
                'package_id' => $package->id,
                'code' => $code,
                'error' => $e->getMessage(),
            ]);

            if ($isCreatedOnRouter) {
                $this->removeOrphanRouterUser($code);
            }

            return null;
        }

        return $account;
    }

    /**
     * L'utilisateur existe déjà sur RouterOS mais l'enregistrement local a
     * échoué : sans ce retrait, il resterait utilisable sur le routeur tout
     * en étant invisible dans l'admin. Un échec ici est seulement journalisé,
     * pour respecter le contrat "ne lève jamais d'exception" de handle().
     */
    private function removeOrphanRouterUser(string $code): void
    {
        try {
            MikroTik::hotspot()->deleteUser($code);
        } catch (\Throwable $e) {
            Log::error('RouterOS: utilisateur Hotspot orphelin non supprimé', [
                'code' => $code,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * RouterOS 7.24 rejette `rate-limit` posé directement sur
     * `/ip/hotspot/user/add` ("unknown parameter rate-limit", vérifié à la
     * main sur le routeur réel) : la limite de débit ne peut être posée que
     * via un profil Hotspot référencé par `profile`. Un profil par palier de
     * débit est réutilisé entre tous les forfaits qui partagent la même
     * vitesse, plutôt que d'en créer un par forfait.
     */
    private function ensureSpeedProfile(int $maxSpeedMbps): string
    {
        if (isset($this->ensuredProfiles[$maxSpeedMbps])) {
            return $this->ensuredProfiles[$maxSpeedMbps];
        }

        $name = "portalfy-{$maxSpeedMbps}M";

        $exists = collect(MikroTik::hotspot()->getProfiles())
            ->contains(fn (array $profile) => ($profile['name'] ?? null) === $name);

        if (! $exists) {
            MikroTik::hotspot()->createProfile([
                'name' => $name,
                'rate-limit' => "{$maxSpeedMbps}M/{$maxSpeedMbps}M",
            ]);
        }

        return $this->ensuredProfiles[$maxSpeedMbps] = $name;
    }
}
