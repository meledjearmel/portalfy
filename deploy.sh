#!/usr/bin/env bash
# Déploiement de Portalfy en production.
# Usage : sudo ./deploy.sh  (depuis /srv/deploiements/portalfy)
#
# Le script se lance en root, mais git, composer, bun et artisan tournent
# sous l'utilisateur de déploiement ($DEPLOY_USER, propriétaire du code) :
# rien n'est construit en root. Seul PM2 reste en root, car il bascule
# lui-même chaque processus en www-data (option `user` de ecosystem.config.cjs).

set -euo pipefail
export PATH="/usr/local/bin:$PATH"

cd "$(dirname "$0")"

DEPLOY_USER="${DEPLOY_USER:-deploy}"
WWW_USER="${WWW_USER:-www-data}"

if [ "$(id -u)" -ne 0 ]; then
    echo "✗ Lance ce script en root (sudo ./deploy.sh) : il bascule lui-même en $DEPLOY_USER." >&2
    exit 1
fi

DEPLOY_HOME="$(getent passwd "$DEPLOY_USER" | cut -d: -f6)"

# Exécute une commande en tant que $DEPLOY_USER, avec son HOME (clé SSH, caches composer/bun).
as_deploy() {
    runuser -u "$DEPLOY_USER" -- env HOME="$DEPLOY_HOME" PATH="/usr/local/bin:/usr/bin:/bin" "$@"
}

echo "→ Nettoyage des caches"
# Ignoré au tout premier déploiement (vendor/ pas encore installé)
# Toléré en échec : au premier déploiement la table cache n'existe pas encore (CACHE_STORE=database)
[ -f vendor/autoload.php ] && { as_deploy php artisan optimize:clear || echo "  (nettoyage ignoré, base pas encore migrée)"; }

echo "→ Récupération du code"
as_deploy git pull --ff-only

echo "→ Dépendances PHP"
as_deploy composer install --no-dev --optimize-autoloader --no-interaction

echo "→ Dépendances Node"
as_deploy bun install --frozen-lockfile

echo "→ Build des assets"
as_deploy bun run build

echo "→ Base de données"
as_deploy php artisan migrate --force
as_deploy php artisan db:seed --force

echo "→ Lien public/storage (logo et fond du portail, si absent)"
[ -L public/storage ] || as_deploy php artisan storage:link

echo "→ Caches Laravel"
as_deploy php artisan optimize:clear
as_deploy php artisan optimize

echo "→ Droits : code à $DEPLOY_USER, écriture $WWW_USER sur storage et bootstrap/cache"
chown -R "$DEPLOY_USER":"$WWW_USER" .
for dir in storage bootstrap/cache; do
    setfacl -R -m u:"$WWW_USER":rwX,u:"$DEPLOY_USER":rwX "$dir"
    setfacl -R -d -m u:"$WWW_USER":rwX,u:"$DEPLOY_USER":rwX "$dir"
done

echo "→ Redémarrage des processus (queue, planificateur, Reverb)"
as_deploy php artisan queue:restart
as_deploy php artisan reverb:restart || true
pm2 startOrReload ecosystem.config.cjs
pm2 save

echo "✓ Déploiement terminé"
pm2 status
