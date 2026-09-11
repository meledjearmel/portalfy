<?php

namespace App\Contracts;

use App\Models\Order;

/**
 * Abstraction fine au-dessus du fournisseur de paiement (GeniusPay pour
 * l'instant), pour rester ouvert à un changement futur sans toucher au
 * reste de l'application.
 */
interface PaymentGatewayContract
{
    /**
     * Initie un paiement hébergé pour la commande et retourne les
     * identifiants fournisseur à persister sur l'Order.
     */
    public function createPayment(Order $order): PaymentInitiation;

    /**
     * Retourne l'URL de paiement hébergé pour une commande déjà initiée
     * (Order::$payment_reference renseigné par createPayment()).
     */
    public function getPaymentUrl(Order $order): string;
}
