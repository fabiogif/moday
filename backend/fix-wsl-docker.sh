#!/bin/bash

echo "🐧 Corrigindo problemas do Docker no WSL..."

# Configurar para suprimir warnings de deprecação do Dotenv
export DOTENV_SUPPRESS_DEPRECATION_WARNINGS=true

# Parar containers se estiverem rodando
echo "🛑 Parando containers existentes..."
docker-compose down

# Corrigir permissões do Git
echo "🔧 Corrigindo permissões do Git..."
git config --global --add safe.directory /var/www/html
git config --global --add safe.directory .

# Remover arquivos problemáticos
echo "🧹 Limpando arquivos de cache..."
rm -f composer.lock
rm -rf vendor/
rm -rf ~/.composer/cache/

# Reinstalar dependências com flags específicas para WSL
echo "📦 Reinstalando dependências para WSL..."
composer install --no-interaction --prefer-dist --optimize-autoloader --ignore-platform-reqs

# Se ainda houver problemas, tentar update
if [ $? -ne 0 ]; then
    echo "🔄 Tentando composer update..."
    composer update --no-interaction --prefer-dist --optimize-autoloader --ignore-platform-reqs
fi

# Configurar permissões para Docker
echo "🔐 Configurando permissões para Docker..."
sudo chown -R $USER:$USER .
sudo chmod -R 755 .

# Configurar variáveis de ambiente para WSL
echo "🌍 Configurando variáveis de ambiente para WSL..."
export WWWGROUP=1000
export WWWUSER=1000

# Criar arquivo .env se não existir
if [ ! -f ".env" ]; then
    echo "📝 Criando arquivo .env..."
    cp .env.example .env
fi

# Configurar banco para Docker
echo "🗄️ Configurando banco de dados..."
sed -i 's/DB_CONNECTION=.*/DB_CONNECTION=mysql/' .env
sed -i 's/DB_HOST=.*/DB_HOST=mysql/' .env
sed -i 's/DB_PORT=.*/DB_PORT=3306/' .env
sed -i 's/DB_DATABASE=.*/DB_DATABASE=laravel/' .env
sed -i 's/DB_USERNAME=.*/DB_USERNAME=sail/' .env
sed -i 's/DB_PASSWORD=.*/DB_PASSWORD=password/' .env

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

echo "✅ Configuração do WSL concluída!"
echo ""
echo "🚀 Para iniciar o Docker:"
echo "   docker-compose up -d"
echo ""
echo "📋 Para executar migrações:"
echo "   docker-compose exec laravel.test php artisan migrate"
