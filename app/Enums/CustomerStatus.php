<?php

namespace App\Enums;

enum CustomerStatus: string
{
    case Active = 'active';
    case Suspended = 'suspended';

    /**
     * Libellé français affiché dans l'écran admin des clients.
     */
    public function label(): string
    {
        return match ($this) {
            self::Active => 'Actif',
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
            self::Suspended => 'red',
        };
    }
}
