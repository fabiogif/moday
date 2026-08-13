#!/bin/bash
# Garante execução a partir de backend/ (este arquivo fica em backend/scripts/)
cd "$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)" || exit 1

# Script para configurar ambiente Docker
echo "🐳 Configurando ambiente Docker..."

# Configurar para suprimir warnings de deprecação do Dotenv
export DOTENV_SUPPRESS_DEPRECATION_WARNINGS=true

# Verificar se o arquivo .env existe
if [ ! -f ".env" ]; then
    echo "⚠️  Arquivo .env não encontrado. Copiando .env.example..."
    cp .env.example .env
fi

# Configurar variáveis de ambiente para Docker
echo "🔧 Configurando variáveis de ambiente..."

# Configurar banco de dados para Docker
sed -i 's/DB_CONNECTION=.*/DB_CONNECTION=mysql/' .env
sed -i 's/DB_HOST=.*/DB_HOST=mysql/' .env
sed -i 's/DB_PORT=.*/DB_PORT=3306/' .env
sed -i 's/DB_DATABASE=.*/DB_DATABASE=laravel/' .env
sed -i 's/DB_USERNAME=.*/DB_USERNAME=sail/' .env
sed -i 's/DB_PASSWORD=.*/DB_PASSWORD=password/' .env

# Configurar Redis para Docker
sed -i 's/REDIS_HOST=.*/REDIS_HOST=redis/' .env
sed -i 's/CACHE_STORE=.*/CACHE_STORE=redis/' .env

# Configurar Mail para Docker
sed -i 's/MAIL_HOST=.*/MAIL_HOST=mailpit/' .env
sed -i 's/MAIL_PORT=.*/MAIL_PORT=1025/' .env

# Gerar chave da aplicação se não existir
if ! grep -q "APP_KEY=" .env || [ -z "$(grep APP_KEY .env | cut -d '=' -f2)" ]; then
    echo "🔑 Gerando chave da aplicação..."
    php artisan key:generate
fi

# Configurar JWT_SECRET se não existir
if ! grep -q "JWT_SECRET=" .env || [ -z "$(grep JWT_SECRET .env | cut -d '=' -f2)" ]; then
    echo "🔐 Configurando JWT secret..."
    JWT_SECRET=$(php -r "echo bin2hex(random_bytes(32));")
    if grep -q "JWT_SECRET=" .env; then
        sed -i "s/JWT_SECRET=.*/JWT_SECRET=$JWT_SECRET/" .env
    else
        echo "JWT_SECRET=$JWT_SECRET" >> .env
    fi
    echo "✅ JWT secret configurado!"
fi

# Configurar variáveis Docker
if ! grep -q "WWWGROUP=" .env; then
    echo "WWWGROUP=1000" >> .env
fi

if ! grep -q "WWWUSER=" .env; then
    echo "WWWUSER=1000" >> .env
fi

if ! grep -q "SAIL_XDEBUG_MODE=" .env; then
    echo "SAIL_XDEBUG_MODE=develop,debug" >> .env
fi

if ! grep -q "SAIL_XDEBUG_CONFIG=" .env; then
    echo "SAIL_XDEBUG_CONFIG=client_host=host.docker.internal" >> .env
fi

echo "✅ Configuração do ambiente Docker concluída!"
echo ""
echo "🚀 Para iniciar o ambiente Docker, execute:"
echo "   ./vendor/bin/sail up -d"
echo ""
echo "📋 Para executar migrações:"
echo "   ./vendor/bin/sail artisan migrate"
echo ""
echo "🧪 Para executar testes:"
echo "   ./vendor/bin/sail artisan test"
