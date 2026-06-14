#!/bin/bash
# Corrige credenciais MySQL em produção (erro: Access denied for user 'sail')
set -e

CONTAINER="${BACKEND_CONTAINER:-moday-backend}"

echo "🔧 Corrigindo DB_* no .env de produção..."

if ! docker ps --format '{{.Names}}' | grep -q "^${CONTAINER}$"; then
  echo "❌ Container ${CONTAINER} não está rodando."
  exit 1
fi

docker exec "${CONTAINER}" bash -c 'grep -E "^DB_" /var/www/html/.env || true'

echo ""
echo "📝 Atualizando credenciais (moday_user / moday_production)..."

docker exec "${CONTAINER}" bash -c '
cd /var/www/html
for key in DB_CONNECTION DB_HOST DB_PORT DB_DATABASE DB_USERNAME DB_PASSWORD; do
  sed -i "/^${key}=/d" .env
done
cat >> .env << EOF

DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=moday_production
DB_USERNAME=moday_user
DB_PASSWORD=Moday@2026!Secure
EOF
'

echo "✅ Novo DB_*:"
docker exec "${CONTAINER}" bash -c 'grep -E "^DB_" /var/www/html/.env'

echo ""
echo "🔄 Limpando cache de config..."
docker exec "${CONTAINER}" php artisan config:clear
docker exec "${CONTAINER}" php artisan config:cache

echo ""
echo "🧪 Testando conexão..."
docker exec "${CONTAINER}" php artisan db:show 2>/dev/null || \
  docker exec "${CONTAINER}" php -r "
    require \"vendor/autoload.php\";
    \$app = require_once \"bootstrap/app.php\";
    \$app->make(\"Illuminate\\Contracts\\Console\\Kernel\")->bootstrap();
    echo \"Plans: \" . DB::table(\"plans\")->count() . PHP_EOL;
  " 2>/dev/null || echo "(rode manualmente se falhar)"

echo ""
echo "🌐 Teste HTTP:"
curl -s -o /dev/null -w "  /api/public/plans → HTTP %{http_code}\n" http://127.0.0.1:8000/api/public/plans || true
echo "✅ Concluído!"
