<?php

namespace App\Console\Commands;

use App\Enums\HotspotAccountStatus;
use App\Models\HotspotAccount;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use ZillEAli\MikrotikLaravel\Facades\MikroTik;

#[Signature('app:sync-hotspot-accounts')]
#[Description('Reporte dans l\'admin les codes entamés et épuisés d\'après le temps consommé sur le routeur.')]
class SyncHotspotAccounts extends Command
{
    /**
     * Le routeur décompte seul le temps de chaque code (`limit-uptime`,
     * temps connecté uniquement : 20 min aujourd'hui, la suite demain) et
     * bloque un code épuisé. Cette tâche ne coupe donc jamais rien : elle
     * reflète seulement l'`uptime` RouterOS en local, y compris pour un code
     * saisi directement sur la page de connexion MikroTik sans passer par le
     * portail.
     */
    public function handle(): void
    {
        $accounts = HotspotAccount::query()
            ->where('status', HotspotAccountStatus::Active)
            ->get()
            ->keyBy('code');

        if ($accounts->isEmpty()) {
            return;
        }

        try {
            $routerUsers = MikroTik::hotspot()->getUsers();
        } catch (\Throwable $e) {
            Log::warning('RouterOS: synchronisation des codes impossible', ['error' => $e->getMessage()]);

            return;
        }

        foreach ($routerUsers as $routerUser) {
            $account = $accounts->get($routerUser['name'] ?? '');
            $uptimeSeconds = self::parseUptime($routerUser['uptime'] ?? '0s');

            if (! $account || $uptimeSeconds === 0) {
                continue;
            }

            if ($account->activated_at === null) {
                $account->activated_at = now()->subSeconds($uptimeSeconds);
            }

            $limitSeconds = self::parseUptime($routerUser['limit-uptime'] ?? '');

            if ($limitSeconds > 0 && $uptimeSeconds >= $limitSeconds) {
                $account->status = HotspotAccountStatus::Used;
            }

            $account->save();
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
