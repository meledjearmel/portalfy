<?php

namespace App\Enums;

enum OrderStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Failed = 'failed';
    case Expired = 'expired';
    case Refunded = 'refunded';

    /**
     * Libellé français affiché dans les écrans admin (commandes, tableau de
     * bord). Centralisé ici pour éviter que chaque écran ne redérive sa
     * propre correspondance statut → libellé.
     */
    public function label(): string
    {
        return match ($this) {
            self::Pending => 'En attente',
            self::Paid => 'Payée',
            self::Failed => 'Échouée',
            self::Expired => 'Expirée',
            self::Refunded => 'Remboursée',
        };
    }

    /**
     * Couleur du badge Flux associé, dans les écrans admin.
     */
    public function color(): string
    {
        return match ($this) {
            self::Paid => 'lime',
            self::Pending => 'amber',
            self::Failed, self::Expired => 'red',
            self::Refunded => 'zinc',
        };
    }
}
