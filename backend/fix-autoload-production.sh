#!/bin/bash
# Script para executar NO CONSOLE DO APP (Digital Ocean)
# Este script regenera o autoload do Composer e limpa todos os caches

echo "=========================================="
echo "FIX: Regenerar Autoload - Contracts"
echo "=========================================="
echo ""

cd ~

echo "1. Verificando estrutura de diretórios..."
if [ -d "app/Repositories/Contracts" ]; then
    echo "✅ Diretório Contracts existe"
    ls -la app/Repositories/Contracts/ | head -10
else
    echo "❌ Diretório Contracts NÃO existe!"
    echo "Verificando se existe contracts (minúsculo):"
    ls -la app/Repositories/ | grep -i contract
fi

echo ""
echo "2. Verificando BaseRepositoryInterface..."
if [ -f "app/Repositories/Contracts/BaseRepositoryInterface.php" ]; then
    echo "✅ BaseRepositoryInterface.php existe"
    head -5 app/Repositories/Contracts/BaseRepositoryInterface.php
else
    echo "❌ BaseRepositoryInterface.php NÃO EXISTE!"
fi

echo ""
echo "3. Regenerando autoload do Composer..."
composer dump-autoload -o

echo ""
echo "4. Limpando todos os caches do Laravel..."
php artisan clear-compiled
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

echo ""
echo "5. Recacheando otimizado..."
php artisan config:cache
php artisan route:cache

echo ""
echo "6. Verificando se interface pode ser carregada..."
php -r "
require 'vendor/autoload.php';
try {
    if (interface_exists('App\\Repositories\\Contracts\\BaseRepositoryInterface')) {
        echo '✅ BaseRepositoryInterface pode ser carregado!\n';
    } else {
        echo '❌ BaseRepositoryInterface NÃO encontrado no autoload\n';
    }
} catch (Exception \$e) {
    echo '❌ Erro: ' . \$e->getMessage() . '\n';
}
"

echo ""
echo "=========================================="
echo "Script concluído!"
echo "=========================================="
echo ""
echo "Agora teste a aplicação no navegador."
echo "Se ainda der erro, envie a saída deste script."
