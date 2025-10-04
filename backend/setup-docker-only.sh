#!/bin/bash

echo "🐳 Configurando ambiente Docker (sem dependências locais)..."

# Configurar para suprimir warnings de deprecação do Dotenv
export DOTENV_SUPPRESS_DEPRECATION_WARNINGS=true

# Parar containers se estiverem rodando
echo "🛑 Parando containers existentes..."
docker-compose down

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

# Iniciar apenas MySQL e Redis primeiro
echo "🚀 Iniciando MySQL e Redis..."
docker-compose up -d mysql redis

# Aguardar MySQL estar pronto
echo "⏳ Aguardando MySQL estar pronto..."
sleep 30

# Verificar se MySQL está rodando
echo "🔍 Verificando status dos containers..."
docker-compose ps

# Agora iniciar o container Laravel
echo "🚀 Iniciando container Laravel..."
docker-compose up -d laravel.test

# Aguardar um pouco
echo "⏳ Aguardando container Laravel..."
sleep 10

# Verificar logs do Laravel
echo "📋 Verificando logs do Laravel..."
docker-compose logs laravel.test

echo "✅ Configuração concluída!"
echo ""
echo "🌐 Aplicação disponível em: http://localhost"
echo "🗄️  MySQL disponível em: localhost:3306"
echo "🔴 Redis disponível em: localhost:6379"
echo ""
echo "📋 Para executar migrações:"
echo "   docker-compose exec laravel.test php artisan migrate"
echo ""
echo "🧪 Para executar testes:"
echo "   docker-compose exec laravel.test php artisan test"
