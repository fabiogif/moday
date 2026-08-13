#!/bin/bash
# Corrige permissões storage/ e bootstrap/cache no container Laravel
set -e

CONTAINER="${BACKEND_CONTAINER:-moday-backend}"

echo "🔧 Corrigindo permissões Laravel em ${CONTAINER}..."

docker exec "${CONTAINER}" bash -c '
cd /var/www/html

# Criar pastas se não existirem
mkdir -p storage/framework/{sessions,views,cache/data}
mkdir -p storage/logs
mkdir -p bootstrap/cache

# Dono: usuário do PHP-FPM (www-data na imagem php-fpm)
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache

# Garantir que subpastas existem e são graváveis
find storage -type d -exec chmod 775 {} \;
find storage -type f -exec chmod 664 {} \;
find bootstrap/cache -type d -exec chmod 775 {} \;

echo "✅ Permissões aplicadas"
ls -la storage/framework/views | head -5
'

echo ""
echo "🔄 Limpando caches..."
docker exec "${CONTAINER}" php artisan view:clear
docker exec "${CONTAINER}" php artisan cache:clear
docker exec "${CONTAINER}" php artisan config:clear
docker exec "${CONTAINER}" php artisan config:cache

echo ""
echo "🧪 Teste:"
curl -s -o /dev/null -w "  /api/public/plans → HTTP %{http_code}\n" http://127.0.0.1:8000/api/public/plans 2>/dev/null || \
  docker exec "${CONTAINER}" curl -s -o /dev/null -w "  HTTP %{http_code}\n" http://127.0.0.1/api/public/plans 2>/dev/null || true

echo "✅ Concluído!"
