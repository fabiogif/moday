#!/bin/bash

echo "🔧 Configuração Redis para Dev/Prod"
echo "===================================="
echo ""

# Detectar ambiente
if [ -f "/.dockerenv" ] || grep -q docker /proc/1/cgroup 2>/dev/null || [ "$LARAVEL_SAIL" = "1" ]; then
    ENVIRONMENT="development"
    echo "🐳 Ambiente: DESENVOLVIMENTO (Docker detectado)"
else
    ENVIRONMENT="production"
    echo "🚀 Ambiente: PRODUÇÃO"
fi

echo ""
echo "Escolha a configuração:"
echo "1) Usar Redis (desenvolvimento com Docker)"
echo "2) NÃO usar Redis (produção sem Redis - fallback para file/sync)"
echo ""
read -p "Opção [1-2]: " choice

case $choice in
    1)
        echo ""
        echo "✅ Configurando para usar Redis..."
        
        # Atualizar .env
        sed -i.bak \
            -e 's/^BROADCAST_DRIVER=.*/BROADCAST_DRIVER=reverb/' \
            -e 's/^CACHE_DRIVER=.*/CACHE_DRIVER=redis/' \
            -e 's/^QUEUE_CONNECTION=.*/QUEUE_CONNECTION=redis/' \
            -e 's/^SESSION_DRIVER=.*/SESSION_DRIVER=redis/' \
            .env
        
        # Adicionar CACHE_STORE se não existir
        if ! grep -q "CACHE_STORE" .env; then
            sed -i.bak '/^CACHE_DRIVER=/a\
CACHE_STORE=redis' .env
        else
            sed -i.bak 's/^CACHE_STORE=.*/CACHE_STORE=redis/' .env
        fi
        
        echo "✅ Configuração para Redis aplicada!"
        ;;
    2)
        echo ""
        echo "✅ Configurando fallback sem Redis..."
        
        # Atualizar .env
        sed -i.bak \
            -e 's/^BROADCAST_DRIVER=.*/BROADCAST_DRIVER=log/' \
            -e 's/^CACHE_DRIVER=.*/CACHE_DRIVER=file/' \
            -e 's/^QUEUE_CONNECTION=.*/QUEUE_CONNECTION=sync/' \
            -e 's/^SESSION_DRIVER=.*/SESSION_DRIVER=file/' \
            .env
        
        # Adicionar CACHE_STORE se não existir
        if ! grep -q "CACHE_STORE" .env; then
            sed -i.bak '/^CACHE_DRIVER=/a\
CACHE_STORE=file' .env
        else
            sed -i.bak 's/^CACHE_STORE=.*/CACHE_STORE=file/' .env
        fi
        
        echo "✅ Configuração sem Redis aplicada!"
        ;;
    *)
        echo "❌ Opção inválida!"
        exit 1
        ;;
esac

# Limpar caches
echo ""
echo "🧹 Limpando caches..."
php artisan config:clear
php artisan cache:clear

echo ""
echo "🎯 Configuração concluída!"
echo ""
echo "Drivers configurados:"
php artisan about | grep -A 8 "Drivers"

