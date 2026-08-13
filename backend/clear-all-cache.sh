#!/bin/bash

echo "🧹 Limpando todos os caches do Laravel..."

# Limpar cache de aplicação
php artisan cache:clear
echo "✅ Cache de aplicação limpo"

# Limpar cache de configuração
php artisan config:clear
echo "✅ Cache de configuração limpo"

# Recriar cache de configuração
php artisan config:cache
echo "✅ Cache de configuração recriado"

# Limpar cache de rotas
php artisan route:clear
echo "✅ Cache de rotas limpo"

# Recriar cache de rotas
php artisan route:cache
echo "✅ Cache de rotas recriado"

# Limpar cache de views
php artisan view:clear
echo "✅ Cache de views limpo"

# Limpar cache de eventos
php artisan event:clear
echo "✅ Cache de eventos limpo"

# Otimizar autoloader
composer dump-autoload -o
echo "✅ Autoloader otimizado"

# Limpar cache do OPcache (se disponível)
if php -m | grep -q "Zend OPcache"; then
    php artisan optimize:clear
    echo "✅ OPcache limpo"
fi

echo "✨ Todos os caches foram limpos com sucesso!"
