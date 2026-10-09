<?php

namespace App\Enums;

/**
 * Moyens de paiement proposés sur le portail (Côte d'Ivoire). Le client choisit
 * avant de quitter le portail : la passerelle redirige alors directement vers
 * l'opérateur choisi, sans page de checkout intermédiaire.
 */
enum PaymentMethod: string
{
    case Wave = 'wave';
    case OrangeMoney = 'orange_money';
    case MtnMoney = 'mtn_money';
    case Card = 'card';

    public function label(): string
    {
        return match ($this) {
            self::Wave => 'Wave',
            self::OrangeMoney => 'Orange Money',
            self::MtnMoney => 'MTN Money',
            self::Card => 'Carte bancaire',
        };
    }
}
