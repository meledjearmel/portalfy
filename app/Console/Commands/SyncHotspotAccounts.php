<?php

namespace App\Console\Commands;

use App\Enums\HotspotAccountStatus;
use App\Models\HotspotAccount;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use ZillEAli\MikrotikLaravel\Exceptions\ResourceNotFoundException;
use ZillEAli\MikrotikLaravel\Facades\MikroTik;

#[Signature('app:sync-hotspot-accounts')]
#[Description('Démarre les codes connectés pour la première fois et coupe ceux dont la durée est écoulée.')]
class SyncHotspotAccounts extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): void
    {
        $this->startCodesUsedOnRouter();
        $this->expireFinishedCodes();
    }

    /**
     * Un code peut être saisi directement sur la page de connexion MikroTik,
     * sans passer par le portail (HotspotAccount::activateOnFirstUse) :
     * l'`uptime` RouterOS, qui ne compte que le temps connecté, est alors le
     * seul signe de sa première connexion.
     */
    private function startCodesUsedOnRouter(): void
    {
        $pending = HotspotAccount::query()
            ->with('package')
            ->where('status', HotspotAccountStatus::Active)
            ->whereNull('activated_at')
            ->get()
            ->keyBy('code');

        if ($pending->isEmpty()) {
            return;
        }

        try {
            $routerUsers = MikroTik::hotspot()->getUsers();
        } catch (\Throwable $e) {
            Log::warning('RouterOS: synchronisation des codes impossible', ['error' => $e->getMessage()]);

            return;
        }

        foreach ($routerUsers as $routerUser) {
            $account = $pending->get($routerUser['name'] ?? '');
            $uptimeSeconds = self::parseUptime($routerUser['uptime'] ?? '0s');

            if (! $account || $uptimeSeconds === 0) {
                continue;
            }

            $activatedAt = now()->subSeconds($uptimeSeconds);

            $account->update([
                'activated_at' => $activatedAt,
                'expires_at' => $activatedAt->addMinutes($account->package->duration_minutes),
            ]);
        }
    }

    /**
     * `limit-uptime` ne compte que le temps connecté : sans cette coupure,
     * un code d'une heure entamé puis laissé de côté resterait utilisable
     * indéfiniment. Le routeur est coupé d'abord, le statut local ensuite,
     * jamais l'inverse (voir la suspension dans l'admin).
     */
    private function expireFinishedCodes(): void
    {
        $finished = HotspotAccount::query()
            ->where('status', HotspotAccountStatus::Active)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->get();

        foreach ($finished as $account) {
            try {
                MikroTik::hotspot()->disableUser($account->code);
            } catch (ResourceNotFoundException) {
                // Déjà absent du routeur : rien à couper.
            } catch (\Throwable $e) {
                Log::warning('RouterOS: coupure d\'un code expiré impossible, nouvel essai au prochain passage', [
                    'hotspot_account_id' => $account->id,
                    'error' => $e->getMessage(),
                ]);

                continue;
            }

            try {
                MikroTik::hotspot()->kickHost($account->code);
            } catch (\Throwable) {
                // Pas de session en cours : le compte désactivé suffit.
            }

            $account->update(['status' => HotspotAccountStatus::Expired]);
        }
    }

    /**
     * Durée RouterOS ("1w2d3h4m5s", "45m", "0s") en secondes.
     */
    public static function parseUptime(string $uptime): int
    {
        $units = ['w' => 604800, 'd' => 86400, 'h' => 3600, 'm' => 60, 's' => 1];

        preg_match_all('/(\d+)([wdhms])/', $uptime, $matches, PREG_SET_ORDER);

        return array_sum(array_map(
            fn (array $match): int => (int) $match[1] * $units[$match[2]],
            $matches,
        ));
    }
}
