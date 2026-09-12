<?php

namespace App\Actions;

use Illuminate\Support\Str;

/**
 * Traduit le message d'erreur brut renvoyé par RouterOS (paramètre `error`
 * de la redirection du portail captif après une tentative de connexion
 * échouée) en message utilisateur simple. Ne renvoie jamais le message
 * RouterOS brut.
 *
 * Référence des messages RouterOS (fichier hotspot `errors.txt`, stable
 * entre versions) : internal-error, config-error, not-logged-in,
 * ippool-empty, shutting-down, user-session-limit, license-session-limit,
 * wrong-mac-username, chap-missing, invalid-username, invalid-mac,
 * uptime-limit, traffic-limit, radius-timeout, auth-in-progress,
 * radius-reply.
 *
 * RouterOS regroupe volontairement plusieurs causes (mauvais mot de passe,
 * compte désactivé côté routeur, compte inconnu) sous le même message
 * générique "invalid username or password", pour ne pas révéler laquelle
 * s'applique. On ne peut donc pas distinguer "invalide" de "suspendu" à
 * partir de ce seul message : les deux retombent sur la même reformulation
 * que le parcours "J'ai déjà un code" utilise pour un code introuvable.
 */
class MapHotspotLoginErrorAction
{
    public function handle(string $error): string
    {
        $normalized = Str::lower($error);

        return match (true) {
            str_contains($normalized, 'uptime limit'),
            str_contains($normalized, 'traffic limit') => 'Ce code a expiré.',

            str_contains($normalized, 'no more sessions') => 'Ce code a déjà été utilisé.',

            str_contains($normalized, 'invalid username'),
            str_contains($normalized, 'not allowed to log in') => "Ce code n'est pas valide.",

            str_contains($normalized, 'already authorizing') => 'Veuillez patienter quelques secondes puis réessayer.',

            default => 'La connexion a échoué. Réessayez, ou connectez-vous manuellement avec votre code.',
        };
    }
}
