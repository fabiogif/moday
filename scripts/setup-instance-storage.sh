#!/bin/bash
# Storage local na instância (sem S3/Spaces) — pastas, symlink e permissões.
# Uso: cd ~/apps/moday-backend && bash scripts/setup-instance-storage.sh

set -e

cd "$(dirname "$0")/.."

echo "📁 Configurando storage local da instância..."

if docker ps --format '{{.Names}}' | grep -q '^moday-backend$'; then
  docker exec moday-backend sh -c '
    set -e
    cd /var/www/html
    mkdir -p storage/app/public/{products,logos,coupons,expenses/attachments} storage/app/temp storage/app/backups
    mkdir -p storage/logs storage/framework/{cache/data,sessions,views} bootstrap/cache
    touch storage/logs/laravel.log
    chown -R www-data:www-data storage bootstrap/cache
    chmod -R 775 storage bootstrap/cache
  '
fi

STORAGE_DIRS=(
  storage/app/public/products
  storage/app/public/logos
  storage/app/public/coupons
  storage/app/public/expenses/attachments
  storage/app/temp
  storage/app/backups
  storage/logs
  storage/framework/cache/data
  storage/framework/sessions
  storage/framework/views
)

for dir in "${STORAGE_DIRS[@]}"; do
  mkdir -p "$dir" 2>/dev/null || sudo mkdir -p "$dir"
done

touch storage/logs/laravel.log 2>/dev/null || true

# Garantir .env com driver local (não S3)
ensure_env() {
  local key="$1"
  local value="$2"
  if grep -q "^${key}=" .env 2>/dev/null; then
    sed -i "s|^${key}=.*|${key}=${value}|" .env
  else
    echo "${key}=${value}" >> .env
  fi
}

if [ -f .env ]; then
  ensure_env "FILESYSTEM_DISK" "public"
  ensure_env "FILESYSTEM_PUBLIC_DRIVER" "local"
  # URL pública via Nginx (/storage → backend :8000)
  APP_URL=$(grep '^APP_URL=' .env | cut -d= -f2- | tr -d '"' | tr -d "'")
  APP_URL="${APP_URL:-http://127.0.0.1}"
  # Evitar localhost em produção quando APP_URL estiver incorreto
  if echo "$APP_URL" | grep -qi 'localhost'; then
    APP_URL="http://163.176.233.174"
    ensure_env "APP_URL" "${APP_URL}"
  fi
  ensure_env "FILESYSTEM_PUBLIC_URL" "${APP_URL}/storage"
fi

if docker ps --format '{{.Names}}' | grep -q '^moday-backend$'; then
  docker exec moday-backend sh -c '
    set -e
    cd /var/www/html
    mkdir -p storage/app/public/{products,logos,coupons,expenses/attachments} storage/app/temp storage/app/backups
    rm -f public/storage
    php artisan storage:link --force 2>/dev/null || php artisan storage:link
    chown -R www-data:www-data storage public/storage 2>/dev/null || true
    chmod -R 775 storage
  '

  WWW_UID=$(docker exec moday-backend id -u www-data)
  WWW_GID=$(docker exec moday-backend id -g www-data)
  sudo chown -R "${WWW_UID}:${WWW_GID}" storage 2>/dev/null || \
    chown -R "${WWW_UID}:${WWW_GID}" storage 2>/dev/null || true
  chmod -R 775 storage 2>/dev/null || true

  if [ -L public/storage ] || [ -d public/storage ]; then
    echo "✅ Symlink public/storage → storage/app/public"
  else
    # Fallback manual quando artisan falhar fora do container
    ln -sfn ../storage/app/public public/storage
    echo "✅ Symlink criado manualmente em public/storage"
  fi
else
  rm -f public/storage
  ln -sfn ../storage/app/public public/storage
  chmod -R 775 storage 2>/dev/null || true
  echo "✅ Symlink public/storage (sem container)"
fi

echo "✅ Storage local pronto em: $(pwd)/storage/app/public"
