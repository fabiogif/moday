#!/bin/bash
# Repara cache Laravel e permissões de storage/bootstrap-cache (www-data) no servidor de produção.
# Uso: cd ~/apps/distribtec-backend && bash scripts/fix-production-runtime.sh

set -e

cd "$(dirname "$0")/.."

if ! docker ps --format '{{.Names}}' | grep -q '^distribtec-backend$'; then
  echo "Container distribtec-backend não está rodando."
  exit 1
fi

echo "🔧 Reparando runtime de produção..."

# Cache de config/packages fica desatualizado após deploy; remove para forçar regeneração.
rm -f bootstrap/cache/packages.php bootstrap/cache/services.php bootstrap/cache/config.php 2>/dev/null || true

docker exec distribtec-backend sh -c '
  set -e
  cd /var/www/html
  rm -f bootstrap/cache/packages.php bootstrap/cache/services.php bootstrap/cache/config.php
  mkdir -p storage/logs storage/framework/{cache/data,sessions,views} bootstrap/cache
  touch storage/logs/laravel.log
  chown -R www-data:www-data storage bootstrap/cache
  chmod -R 775 storage bootstrap/cache
  chmod 664 storage/logs/laravel.log
  php artisan package:discover --ansi
  php artisan config:clear
  php artisan cache:clear
'

bash scripts/setup-instance-storage.sh 2>/dev/null || true

WWW_UID=$(docker exec distribtec-backend id -u www-data)
WWW_GID=$(docker exec distribtec-backend id -g www-data)
sudo chown -R "${WWW_UID}:${WWW_GID}" storage bootstrap/cache 2>/dev/null || \
  chown -R "${WWW_UID}:${WWW_GID}" storage bootstrap/cache 2>/dev/null || true

docker compose -f docker-compose.production.yml restart app
sleep 3

CODE=$(curl -s -o /dev/null -w "%{http_code}" http://127.0.0.1:8001/api/public/plans || echo "000")
echo "API /api/public/plans → HTTP ${CODE}"
[ "$CODE" = "200" ] || exit 1
echo "✅ Produção reparada."
