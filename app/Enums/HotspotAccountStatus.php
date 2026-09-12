<?php

namespace App\Enums;

enum HotspotAccountStatus: string
{
    case Active = 'active';
    case Used = 'used';
    case Expired = 'expired';
    case Suspended = 'suspended';

    /**
     * Libellé français affiché dans l'écran admin des comptes Hotspot.
     */
    public function label(): string
    {
        return match ($this) {
            self::Active => 'Actif',
            self::Used => 'Utilisé',
            self::Expired => 'Expiré',
            self::Suspended => 'Suspendu',
        };
    }

    /**
     * Couleur du badge Flux associé, dans l'écran admin.
     */
    public function color(): string
    {
        return match ($this) {
            self::Active => 'lime',
            self::Used, self::Expired => 'zinc',
            self::Suspended => 'red',
        };
    }
}
