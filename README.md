# PortalFy

Portail captif WiFi payant premium — achetez un accès en quelques secondes, sans compte obligatoire, avec paiement mobile money (Wave, Orange Money, MTN Money, carte).

Ce n'est pas un simple portail MikroTik à voucher : PortalFy est pensé comme une vraie plateforme commerciale de WiFi public, moderne et épurée.

## Parcours principal

**Choisir un forfait → Payer → Recevoir un code → Se connecter.**

L'achat est l'action dominante et ne nécessite jamais de compte. Un compte client est une option pour les utilisateurs réguliers (historique, factures, renouvellement plus rapide), jamais un prérequis.

## Stack technique

- **Laravel 13** (application unique, pas de séparation API/SPA)
- **Livewire 4 + Flux (tier gratuit)** pour toute l'interface — portail public et admin
- **Laravel Fortify** pour l'authentification du portail client (guard `customer`) ; les comptes admin (guard `web`) sont créés via artisan, sans auto-inscription
- **Laravel Reverb** pour le temps réel (affichage du code d'accès dès confirmation du paiement)
- **GeniusPay** pour le paiement mobile money, derrière une interface `PaymentGatewayContract` pour rester ouvert à un changement de fournisseur
- **zilleali/mikrotik-laravel** pour l'intégration RouterOS (provisioning des comptes Hotspot)
- **spatie/laravel-activitylog** pour l'audit
- **barryvdh/laravel-dompdf** pour les factures PDF

## Modèle de données

`WifiZoneSetting` (config de la zone, identité configurable par l'admin — jamais codée en dur), `Package` (forfaits), `Order` (commandes), `HotspotAccount` (accès WiFi provisionnés), `Customer` (compte client optionnel), `Invoice` (factures).

## Installation

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm run build
```

En local (Laravel Herd), l'application est servie sur `https://portalfy.test`.

## Tests

```bash
php artisan test
vendor/bin/pint
vendor/bin/phpstan analyse
```

## État d'avancement

Projet en développement actif. Migrations, modèles, guards, structure applicative, thème Flux/Tailwind et écrans publics du parcours principal sont en place ; l'intégration GeniusPay, le provisioning RouterOS, le temps réel Reverb, le parcours compte client et le dashboard admin sont à venir.
