#!/bin/sh
# Uso no servidor: cd ~/apps/moday-backend && sh scripts/production-up.sh
set -e
cd "$(dirname "$0")/.."

docker compose -f docker-compose.production.yml up -d --build
echo "Aguardando MySQL..."
sleep 20
docker compose -f docker-compose.production.yml ps

docker exec moday-backend php artisan migrate --force 2>/dev/null || true
docker exec moday-backend chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true

echo "OK — API em http://127.0.0.1:8000"
