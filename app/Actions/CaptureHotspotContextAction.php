<?php

namespace App\Actions;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Capture le contexte hotspot (mac/ip/link-login/link-orig) envoyé par
 * RouterOS lors de la redirection du portail captif vers la page d'accueil,
 * et le stocke en session pour qu'il survive à toute la navigation (choix
 * du forfait -> paiement -> retour -> code affiché).
 *
 * Ces query params sont fournis par le visiteur (donc modifiables) : ils ne
 * sont jamais utilisés sans validation. `link-login` en particulier doit
 * pointer vers une IP privée du réseau hotspot, sinon un POST username/
 * password forgé plus tard pourrait exfiltrer un code d'accès vers un
 * serveur tiers.
 */
class CaptureHotspotContextAction
{
    public const string SESSION_KEY = 'hotspot.context';

    /**
     * N'écrase jamais un contexte déjà capturé si la requête courante ne
     * porte pas ces query params (navigation interne à l'app, sans eux) ou
     * si l'un d'eux ne passe pas la validation.
     */
    public function handle(Request $request): void
    {
        $mac = $request->query('mac');
        $ip = $request->query('ip');
        $linkLogin = $request->query('link-login');
        $linkOrig = $request->query('link-orig');

        if (! is_string($mac) || ! is_string($ip) || ! is_string($linkLogin)) {
            return;
        }

        if (! $this->isValidMac($mac) || ! $this->isValidIp($ip) || ! $this->isValidLinkLogin($linkLogin)) {
            Log::warning('Hotspot: contexte reçu sur / rejeté (paramètre invalide)', [
                'mac' => $mac,
                'ip' => $ip,
                'link_login' => $linkLogin,
                'client_ip' => $request->ip(),
            ]);

            return;
        }

        $request->session()->put(self::SESSION_KEY, [
            'mac' => strtoupper($mac),
            'ip' => $ip,
            'link_login' => $linkLogin,
            'link_orig' => is_string($linkOrig) && filter_var($linkOrig, FILTER_VALIDATE_URL) !== false
                ? $linkOrig
                : null,
        ]);
    }

    /**
     * Retourne le contexte hotspot actuellement en session, ou null si le
     * visiteur n'est pas arrivé via la redirection du portail captif (ou que
     * sa session ne le porte plus).
     *
     * @return array{mac: string, ip: string, link_login: string, link_orig: ?string}|null
     */
    public function current(): ?array
    {
        return session(self::SESSION_KEY);
    }

    private function isValidMac(string $mac): bool
    {
        return (bool) preg_match('/^[0-9A-Fa-f]{2}(:[0-9A-Fa-f]{2}){5}$/', $mac);
    }

    private function isValidIp(string $ip): bool
    {
        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false;
    }

    /**
     * `link-login` doit être une URL http(s) dont l'hôte est une IP privée
     * (le réseau hotspot RouterOS) : jamais un domaine ou une IP publique,
     * qui permettrait à un lien forgé de faire poster des identifiants vers
     * un serveur contrôlé par un tiers.
     */
    private function isValidLinkLogin(string $url): bool
    {
        $parts = parse_url($url);

        if (! is_array($parts) || ! isset($parts['scheme'], $parts['host'])) {
            return false;
        }

        if (! in_array($parts['scheme'], ['http', 'https'], true)) {
            return false;
        }

        return $this->isPrivateIpv4($parts['host']);
    }

    private function isPrivateIpv4(string $host): bool
    {
        if (filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) === false) {
            return false;
        }

        return filter_var($host, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4 | FILTER_FLAG_NO_PRIV_RANGE) === false;
    }
}
