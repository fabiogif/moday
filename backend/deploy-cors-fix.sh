#!/bin/bash

echo "🚨 CORREÇÃO URGENTE DE CORS - SERVIDOR DE PRODUÇÃO"
echo "=================================================="
echo ""

# Verificar se estamos no diretório correto
if [ ! -f "artisan" ]; then
    echo "❌ Erro: Execute este script no diretório raiz do projeto Laravel"
    exit 1
fi

echo "📍 Diretório atual: $(pwd)"
echo ""

# Fazer backup
echo "📦 Fazendo backup dos arquivos..."
BACKUP_DIR="backup-$(date +%Y%m%d_%H%M%S)"
mkdir -p "$BACKUP_DIR"

cp config/cors.php "$BACKUP_DIR/"
cp app/Http/Middleware/CustomCorsMiddleware.php "$BACKUP_DIR/"
cp .env "$BACKUP_DIR/"

echo "✅ Backup criado em: $BACKUP_DIR"
echo ""

# Verificar estado atual
echo "🔍 Verificando configuração atual..."
echo "CORS Origins atuais:"
grep -A 10 "'allowed_origins'" config/cors.php | grep -E "'http|'https"
echo ""

# Atualizar config/cors.php
echo "📝 Atualizando config/cors.php..."
if ! grep -q "https://moday-nine.vercel.app" config/cors.php; then
    # Encontrar a linha com 'allowed_origins' e adicionar o novo domínio
    sed -i "/'allowed_origins' => array_filter(\[/,/\]),/ {
        /'https:\/\/localhost:3001',/ a\
        'https://moday-nine.vercel.app',
    }" config/cors.php
    echo "✅ Adicionado https://moday-nine.vercel.app ao config/cors.php"
else
    echo "✅ https://moday-nine.vercel.app já está em config/cors.php"
fi

# Atualizar CustomCorsMiddleware.php
echo "📝 Atualizando CustomCorsMiddleware.php..."
if ! grep -q "https://moday-nine.vercel.app" app/Http/Middleware/CustomCorsMiddleware.php; then
    sed -i "/'https:\/\/localhost:3001',/ a\
            'https://moday-nine.vercel.app'," app/Http/Middleware/CustomCorsMiddleware.php
    echo "✅ Adicionado https://moday-nine.vercel.app ao CustomCorsMiddleware.php"
else
    echo "✅ https://moday-nine.vercel.app já está em CustomCorsMiddleware.php"
fi

# Atualizar .env
echo "📝 Atualizando .env..."
if ! grep -q "FRONTEND_URL=" .env; then
    echo "" >> .env
    echo "# CORS Configuration" >> .env
    echo "FRONTEND_URL=https://moday-nine.vercel.app" >> .env
    echo "ADDITIONAL_CORS_ORIGIN=http://localhost:3000" >> .env
    echo "✅ Adicionadas variáveis CORS ao .env"
else
    # Atualizar FRONTEND_URL se já existir
    if grep -q "FRONTEND_URL=http://localhost" .env; then
        sed -i 's/FRONTEND_URL=.*/FRONTEND_URL=https:\/\/moday-nine.vercel.app/' .env
        echo "✅ Atualizado FRONTEND_URL no .env"
    else
        echo "✅ FRONTEND_URL já está configurado corretamente"
    fi
fi

echo ""
echo "🧹 Limpando cache de configuração..."
php artisan config:clear
php artisan config:cache

echo ""
echo "🔍 Verificando configuração após mudanças..."
echo "CORS Origins atualizados:"
grep -A 10 "'allowed_origins'" config/cors.php | grep -E "'http|'https"
echo ""

echo "✅ CONFIGURAÇÃO CORS ATUALIZADA!"
echo ""
echo "🔄 PRÓXIMOS PASSOS:"
echo "1. Reinicie o servidor web:"
echo "   sudo systemctl restart nginx"
echo "   # ou"
echo "   sudo systemctl restart apache2"
echo ""
echo "2. Teste a configuração:"
echo "   curl -X OPTIONS https://orca-app-7hejo.ondigitalocean.app/api/auth/login \\"
echo "     -H \"Origin: http://localhost:3000\" \\"
echo "     -H \"Access-Control-Request-Method: POST\" \\"
echo "     -v"
echo ""
echo "3. Verifique se retorna:"
echo "   access-control-allow-origin: http://localhost:3000"
echo "   (NÃO deve retornar *)"
echo ""
echo "📁 Backup salvo em: $BACKUP_DIR"
