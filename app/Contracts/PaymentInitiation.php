<?php

namespace App\Contracts;

/**
 * Identifiants fournisseur renvoyés après l'initiation d'un paiement,
 * destinés à être persistés sur l'Order (payment_provider, payment_reference).
 */
final readonly class PaymentInitiation
{
    public function __construct(
        public string $provider,
        public string $reference,
        public string $checkoutUrl,
    ) {}
}
