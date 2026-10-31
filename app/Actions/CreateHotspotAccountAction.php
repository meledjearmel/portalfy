<?php

namespace App\Actions;

use App\Enums\CredentialMode;
use App\Enums\HotspotAccountStatus;
use App\Models\HotspotAccount;
use App\Models\Order;
use App\Models\Package;
use App\Models\VoucherBatch;
use App\Models\WifiZoneSetting;
use Illuminate\Support\Facades\Log;
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
    public function handle(Package $package, ?Order $order = null, ?string $comment = null, ?VoucherBatch $batch = null): ?HotspotAccount
    {
        $zone = WifiZoneSetting::current();

        $code = $this->generateUniqueCode();
        $secret = $zone->credential_mode === CredentialMode::Separate
            ? $this->randomCode()
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
                'voucher_batch_id' => $batch?->id,
                'code' => $code,
                'secret' => $secret,
                'status' => HotspotAccountStatus::Active,
                // Un code créé par l'admin peut attendre des jours avant
                // d'être vendu : son accès ne démarre qu'à la première
                // connexion (voir HotspotAccount::activateOnFirstUse). Un
                // code acheté en ligne est utilisé tout de suite.
                'activated_at' => $order ? now() : null,
                'expires_at' => $order ? now()->addMinutes($package->duration_minutes) : null,
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
     * Majuscules et chiffres uniquement, sans les caractères qui se
     * confondent une fois imprimés ou lus à voix haute (0/O, 1/I/L).
     */
    private function randomCode(): string
    {
        $alphabet = 'ABCDEFGHJKMNPQRSTUVWXYZ23456789';

        return collect(range(1, 6))
            ->map(fn (): string => $alphabet[random_int(0, strlen($alphabet) - 1)])
            ->implode('');
    }

    /**
     * Inclut les comptes en corbeille : un code restauré ne doit jamais
     * entrer en collision avec un code émis entre-temps.
     */
    private function generateUniqueCode(): string
    {
        do {
            $code = $this->randomCode();
        } while (HotspotAccount::withTrashed()->where('code', $code)->exists());

        return $code;
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
