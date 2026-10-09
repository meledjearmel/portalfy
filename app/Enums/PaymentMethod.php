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
    case MoovMoney = 'moov_money';
    case Visa = 'visa';
    case Mastercard = 'mastercard';

    public function label(): string
    {
        return match ($this) {
            self::Wave => 'Wave',
            self::OrangeMoney => 'Orange Money',
            self::MtnMoney => 'MTN Money',
            self::MoovMoney => 'Moov Money',
            self::Visa => 'Visa',
            self::Mastercard => 'Mastercard',
        };
    }

    /**
     * Code "payment_method" attendu par GeniusPay : Visa et Mastercard sont
     * présentés séparément au client mais passent tous deux par "card".
     */
    public function gatewayCode(): string
    {
        return match ($this) {
            self::Visa, self::Mastercard => 'card',
            default => $this->value,
        };
    }

    public function logo(): string
    {
        return asset(match ($this) {
            self::Wave => 'images/payment/wave.svg',
            self::OrangeMoney => 'images/payment/orange.svg',
            self::MtnMoney => 'images/payment/mtn.svg',
            self::MoovMoney => 'images/payment/moov.webp',
            self::Visa => 'images/payment/visa.svg',
            self::Mastercard => 'images/payment/mastercard.svg',
        });
    }
}
