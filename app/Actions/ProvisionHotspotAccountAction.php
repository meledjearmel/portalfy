<?php

namespace App\Actions;

use App\Enums\CredentialMode;
use App\Enums\HotspotAccountStatus;
use App\Events\HotspotAccountProvisioned;
use App\Models\HotspotAccount;
use App\Models\Order;
use App\Models\WifiZoneSetting;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use ZillEAli\MikrotikLaravel\Facades\MikroTik;

class ProvisionHotspotAccountAction
{
    /**
     * Génère le code d'accès et crée le compte Hotspot correspondant sur le
     * routeur RouterOS. Ne lève jamais d'exception : une commande payée ne
     * doit pas échouer parce que le routeur est injoignable — l'absence de
     * HotspotAccount laisse l'écran de retour paiement sur "en cours
     * d'activation" (un rattrapage manuel/job de réconciliation reste
     * possible tant que l'Order est payée).
     */
    public function handle(Order $order): ?HotspotAccount
    {
        $package = $order->package;
        $zone = WifiZoneSetting::current();

        $code = Str::upper(Str::random(6));
        $secret = $zone->credential_mode === CredentialMode::Separate
            ? Str::upper(Str::random(6))
            : null;

        $data = [
            'name' => $code,
            'password' => $secret ?? $code,
            'comment' => "Commande {$order->reference}",
            'limit-uptime' => ($package->duration_minutes * 60).'s',
        ];

        if ($package->max_speed_mbps) {
            $data['rate-limit'] = "{$package->max_speed_mbps}M/{$package->max_speed_mbps}M";
        }

        // Les deux étapes sont couvertes par le même catch(\Throwable) : la
        // validation MikroTik (ValidationException étend InvalidArgumentException,
        // pas RuntimeException) et une collision improbable sur le code unique
        // (QueryException) doivent toutes deux respecter le contrat "ne lève
        // jamais d'exception" de cette méthode, même quand elles surviennent
        // après que le compte RouterOS a déjà été créé côté routeur.
        try {
            MikroTik::hotspot()->createUser($data);

            $account = HotspotAccount::create([
                'order_id' => $order->id,
                'code' => $code,
                'secret' => $secret,
                'status' => HotspotAccountStatus::Active,
                'activated_at' => now(),
                'expires_at' => now()->addMinutes($package->duration_minutes),
            ]);
        } catch (\Throwable $e) {
            Log::error('RouterOS: échec de provisioning du compte Hotspot', [
                'order_id' => $order->id,
                'code' => $code,
                'error' => $e->getMessage(),
            ]);

            return null;
        }

        HotspotAccountProvisioned::dispatch($account);

        return $account;
    }
}
