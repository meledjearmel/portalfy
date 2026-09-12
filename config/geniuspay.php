<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Mode Sandbox
    |--------------------------------------------------------------------------
    |
    | Activez le mode sandbox pour les tests. En mode sandbox, aucune
    | transaction réelle n'est effectuée.
    |
    */
    'sandbox_mode' => env('GENIUSPAY_SANDBOX', true),

    /*
    |--------------------------------------------------------------------------
    | Clés API
    |--------------------------------------------------------------------------
    |
    | Vos clés API GeniusPay. Utilisez les clés sandbox pour les tests
    | et les clés live pour la production.
    |
    */
    'api_key' => env('GENIUSPAY_API_KEY', ''),
    'api_secret' => env('GENIUSPAY_API_SECRET', ''),

    /*
    |--------------------------------------------------------------------------
    | URL de base de l'API
    |--------------------------------------------------------------------------
    |
    | URL de l'API GeniusPay. Généralement, vous n'avez pas besoin
    | de modifier cette valeur.
    |
    */
    'base_url' => env('GENIUSPAY_BASE_URL', 'https://pay.genius.ci/api/v1/merchant'),

    /*
    |--------------------------------------------------------------------------
    | Secret Webhook
    |--------------------------------------------------------------------------
    |
    | Secret pour vérifier l'authenticité des webhooks entrants.
    | Générez une chaîne aléatoire sécurisée.
    |
    */
    'webhook_secret' => env('GENIUSPAY_WEBHOOK_SECRET', ''),

    /*
    |--------------------------------------------------------------------------
    | Chemin du Webhook
    |--------------------------------------------------------------------------
    |
    | Le chemin où les webhooks GeniusPay seront reçus.
    |
    */
    'webhook_path' => env('GENIUSPAY_WEBHOOK_PATH', 'geniuspay/webhook'),

    /*
    |--------------------------------------------------------------------------
    | URLs de redirection par défaut
    |--------------------------------------------------------------------------
    |
    | URLs de redirection utilisées si non spécifiées lors de la création
    | d'un paiement.
    |
    */
    'success_url' => env('GENIUSPAY_SUCCESS_URL', '/payment/success'),
    'error_url' => env('GENIUSPAY_ERROR_URL', '/payment/error'),

    /*
    |--------------------------------------------------------------------------
    | Mode Debug
    |--------------------------------------------------------------------------
    |
    | Activez pour logger les requêtes et réponses API.
    | Ne pas activer en production.
    |
    */
    'debug' => env('GENIUSPAY_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Timeout
    |--------------------------------------------------------------------------
    |
    | Timeout des requêtes API en secondes.
    |
    */
    'timeout' => env('GENIUSPAY_TIMEOUT', 30),

    /*
    |--------------------------------------------------------------------------
    | Devise par défaut
    |--------------------------------------------------------------------------
    |
    | Devise utilisée par défaut pour les paiements.
    |
    */
    'currency' => env('GENIUSPAY_CURRENCY', 'XOF'),

    /*
    |--------------------------------------------------------------------------
    | Middleware Webhook
    |--------------------------------------------------------------------------
    |
    | Middleware appliqué aux routes webhook.
    |
    */
    'webhook_middleware' => ['api'],

    /*
    |--------------------------------------------------------------------------
    | Stocker les transactions
    |--------------------------------------------------------------------------
    |
    | Si activé, les transactions seront stockées dans la base de données.
    |
    */
    'store_transactions' => env('GENIUSPAY_STORE_TRANSACTIONS', false),

    /*
    |--------------------------------------------------------------------------
    | Table des transactions
    |--------------------------------------------------------------------------
    |
    | Nom de la table pour stocker les transactions.
    |
    */
    'transactions_table' => 'geniuspay_transactions',
];
