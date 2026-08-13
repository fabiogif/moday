#!/bin/bash
# Fix __PHP_Incomplete_Class error caused by serialized cache with old namespaces

echo "========================================"
echo "FIX: __PHP_Incomplete_Class Error"
echo "========================================"
echo ""

cd ~

echo "1. Limpando TODOS os caches (incluindo serializados)..."
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan clear-compiled
php artisan optimize:clear

echo ""
echo "2. Limpando cache do framework manualmente..."
rm -rf bootstrap/cache/*.php
rm -rf storage/framework/cache/data/*
rm -rf storage/framework/sessions/*
rm -rf storage/framework/views/*

echo ""
echo "3. Regenerando autoload..."
composer dump-autoload -o

echo ""
echo "4. Recacheando otimizado..."
php artisan config:cache
php artisan route:cache

echo ""
echo "5. Testando se pode criar instâncias..."
php -r "
require 'vendor/autoload.php';
\$app = require_once 'bootstrap/app.php';
\$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

try {
    echo 'Testando CategoryService...\n';
    \$service = app('App\Services\CategoryService');
    echo '✅ CategoryService criado com sucesso!\n';
} catch (Exception \$e) {
    echo '❌ Erro: ' . \$e->getMessage() . '\n';
}
"

echo ""
echo "========================================"
echo "✅ Cache serializado limpo!"
echo "========================================"
echo ""
echo "IMPORTANTE: Se você usar Redis/Memcached,"
echo "também precisa limpar o cache externo:"
echo ""
echo "  redis-cli FLUSHALL"
echo "  ou"
echo "  php artisan cache:flush"
echo ""
echo "Agora teste a aplicação no navegador."
