#!/bin/bash
# Repara cache Laravel (sem Sail/dev) e permissões de storage no servidor de produção.
# Uso: cd ~/apps/moday-backend && bash scripts/fix-production-runtime.sh

set -e

cd "$(dirname "$0")/.."

if ! docker ps --format '{{.Names}}' | grep -q '^moday-backend$'; then
  echo "Container moday-backend não está rodando."
  exit 1
fi

echo "🔧 Reparando runtime de produção..."

# Cache no host (volume montado) não pode referenciar pacotes dev (Sail, Collision, etc.)
rm -f bootstrap/cache/packages.php bootstrap/cache/services.php bootstrap/cache/config.php 2>/dev/null || true

docker exec moday-backend sh -c '
  set -e
  cd /var/www/html
  rm -f bootstrap/cache/packages.php bootstrap/cache/services.php bootstrap/cache/config.php
  mkdir -p storage/logs storage/framework/{cache/data,sessions,views} bootstrap/cache
  touch storage/logs/laravel.log
  chown -R www-data:www-data storage bootstrap/cache
  chmod -R 775 storage bootstrap/cache
  chmod 664 storage/logs/laravel.log
  composer install --no-dev --optimize-autoloader --no-interaction
  php artisan package:discover --ansi
  php artisan config:clear
  php artisan cache:clear
'

bash scripts/setup-instance-storage.sh 2>/dev/null || true

WWW_UID=$(docker exec moday-backend id -u www-data)
WWW_GID=$(docker exec moday-backend id -g www-data)
sudo chown -R "${WWW_UID}:${WWW_GID}" storage bootstrap/cache 2>/dev/null || \
  chown -R "${WWW_UID}:${WWW_GID}" storage bootstrap/cache 2>/dev/null || true

if docker exec moday-backend grep -qi sail bootstrap/cache/packages.php 2>/dev/null; then
  echo "❌ Sail ainda presente em bootstrap/cache/packages.php"
  exit 1
fi

docker compose -f docker-compose.production.yml restart app
sleep 3

CODE=$(curl -s -o /dev/null -w "%{http_code}" http://127.0.0.1/api/public/plans || echo "000")
echo "API /api/public/plans → HTTP ${CODE}"
[ "$CODE" = "200" ] || exit 1
echo "✅ Produção reparada."
