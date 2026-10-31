#!/usr/bin/env bash
# Déploiement de Portalfy en production.
# Usage : ./deploy.sh  (depuis /srv/deploiements/portalfy)

set -euo pipefail

cd "$(dirname "$0")"

echo "→ Nettoyage des caches"
php artisan optimize:clear

echo "→ Récupération du code"
git pull --ff-only

echo "→ Dépendances PHP"
composer install --no-dev --optimize-autoloader --no-interaction

echo "→ Dépendances Node"
pnpm install --frozen-lockfile

echo "→ Build des assets"
pnpm run build

echo "→ Base de données"
php artisan migrate --force

echo "→ Lien public/storage (logo et fond du portail, si absent)"
[ -L public/storage ] || php artisan storage:link

echo "→ Caches Laravel"
php artisan optimize:clear
php artisan optimize

echo "→ Propriétaire www-data (PHP-FPM et PM2 tournent en www-data, le déploiement en root)"
chown -R www-data:www-data storage bootstrap/cache

echo "→ Redémarrage des processus (queue, planificateur, Reverb)"
php artisan queue:restart
php artisan reverb:restart || true
pm2 startOrReload ecosystem.config.cjs
pm2 save

echo "✓ Déploiement terminé"
pm2 status
