#!/bin/bash
# Garante execução a partir de backend/ (este arquivo fica em backend/scripts/)
cd "$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)" || exit 1

echo "🐳 Configurando ambiente Docker para Laravel..."

# Configurar para suprimir warnings de deprecação do Dotenv
export DOTENV_SUPPRESS_DEPRECATION_WARNINGS=true

# Criar arquivo .env se não existir
if [ ! -f ".env" ]; then
    echo "📝 Criando arquivo .env..."
    cat > .env << 'EOF'
APP_NAME=Laravel
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_TIMEZONE=UTC
APP_URL=http://localhost

APP_LOCALE=en
APP_FALLBACK_LOCALE=en
APP_FAKER_LOCALE=en_US

APP_MAINTENANCE_DRIVER=file
APP_MAINTENANCE_STORE=database

BCRYPT_ROUNDS=12

LOG_CHANNEL=stack
LOG_STACK=single
LOG_DEPRECATIONS_CHANNEL=null
LOG_LEVEL=debug

# Database Configuration for Docker
DB_CONNECTION=mysql
DB_HOST=mysql
DB_PORT=3306
DB_DATABASE=laravel
DB_USERNAME=sail
DB_PASSWORD=password

SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_ENCRYPT=false
SESSION_PATH=/
SESSION_DOMAIN=null

BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local
QUEUE_CONNECTION=database

CACHE_STORE=redis
CACHE_PREFIX=

MEMCACHED_HOST=memcached

REDIS_CLIENT=phpredis
REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379

MAIL_MAILER=smtp
MAIL_HOST=mailpit
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"

AWS_ACCESS_KEY_ID=
AWS_SECRET_ACCESS_KEY=
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=
AWS_USE_PATH_STYLE_ENDPOINT=false

VITE_APP_NAME="${APP_NAME}"

# JWT Configuration
JWT_SECRET=
JWT_TTL=60

# Dotenv Configuration
DOTENV_SUPPRESS_DEPRECATION_WARNINGS=true

# Docker Configuration
WWWGROUP=1000
WWWUSER=1000
SAIL_XDEBUG_MODE=develop,debug
SAIL_XDEBUG_CONFIG="client_host=host.docker.internal"
EOF
fi

# Gerar chave da aplicação
echo "🔑 Gerando chave da aplicação..."
php artisan key:generate --force

# Configurar JWT_SECRET
echo "🔐 Configurando JWT secret..."
JWT_SECRET=$(php -r "echo bin2hex(random_bytes(32));")
if grep -q "JWT_SECRET=" .env; then
    sed -i "s/JWT_SECRET=.*/JWT_SECRET=$JWT_SECRET/" .env
else
    echo "JWT_SECRET=$JWT_SECRET" >> .env
fi

echo "✅ Configuração concluída!"
echo ""
echo "🚀 Para iniciar o ambiente Docker:"
echo "   docker-compose up -d"
echo ""
echo "📋 Para executar migrações:"
echo "   docker-compose exec laravel.test php artisan migrate"
echo ""
echo "🧪 Para executar testes:"
echo "   docker-compose exec laravel.test php artisan test"
