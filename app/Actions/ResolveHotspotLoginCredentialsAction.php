<?php

namespace App\Actions;

use App\Enums\CredentialMode;
use App\Models\HotspotAccount;
use App\Models\WifiZoneSetting;

/**
 * Détermine les identifiants à soumettre à RouterOS pour connecter un
 * HotspotAccount au hotspot, selon le `credential_mode` de la zone : en
 * mode `unique`, le code sert à la fois de nom d'utilisateur et de mot de
 * passe (c'est ainsi que ProvisionHotspotAccountAction crée l'utilisateur
 * RouterOS) ; en mode `separate`, le mot de passe est le `secret` généré
 * séparément au provisioning.
 */
class ResolveHotspotLoginCredentialsAction
{
    /**
     * @return array{username: string, password: string}
     */
    public function handle(HotspotAccount $account, ?WifiZoneSetting $zone = null): array
    {
        $zone ??= WifiZoneSetting::current();

        return [
            'username' => $account->code,
            'password' => $zone->credential_mode === CredentialMode::Separate
                ? ($account->secret ?? $account->code)
                : $account->code,
        ];
    }
}
