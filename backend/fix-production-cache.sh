#!/bin/bash

# Script para limpar cache e resolver problemas em produção
# Execute este script no servidor de produção

echo "================================="
echo "Limpando Cache do Laravel"
echo "================================="
echo ""

# Verificar se estamos no diretório correto
if [ ! -f "artisan" ]; then
    echo "❌ Erro: arquivo artisan não encontrado"
    echo "Execute este script no diretório raiz do Laravel"
    exit 1
fi

echo "📦 Limpando todos os caches..."
php artisan optimize:clear

echo "✅ Cache limpo!"
echo ""

echo "🔧 Recriando caches de configuração..."
php artisan config:cache
php artisan route:cache
php artisan view:cache

echo "✅ Caches recriados!"
echo ""

echo "🔍 Verificando configurações..."
php artisan about

echo ""
echo "================================="
echo "✨ Processo concluído!"
echo "================================="
echo ""
echo "Próximos passos:"
echo "1. Teste o login (erro de Rate Limiter deve estar resolvido)"
echo "2. Teste a listagem de produtos (devem aparecer com IDs)"
echo "3. Teste editar produto (não deve dar erro de validação)"
echo ""
