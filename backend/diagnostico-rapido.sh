#!/bin/bash
# Script de diagnóstico - Execute no console do App Platform

echo "=== DIAGNÓSTICO CORS ==="
echo ""

# 1. Verificar se arquivo foi atualizado
echo "1. Verificando CustomCorsMiddleware.php:"
if grep -q "clownfish-app-rr5rv.ondigitalocean.app" ~/app/Http/Middleware/CustomCorsMiddleware.php; then
    echo "✅ URL de produção ENCONTRADA no middleware"
else
    echo "❌ URL de produção NÃO ENCONTRADA - deploy não aplicado!"
    echo "Conteúdo atual da variável allowedOrigins:"
    grep -A 8 "allowedOrigins" ~/app/Http/Middleware/CustomCorsMiddleware.php
fi

echo ""

# 2. Verificar variável de ambiente
echo "2. Verificando variável FRONTEND_URL:"
if printenv | grep -q "FRONTEND_URL"; then
    printenv | grep "FRONTEND_URL"
    echo "✅ FRONTEND_URL configurada"
else
    echo "❌ FRONTEND_URL não configurada"
fi

echo ""

# 3. Verificar se middleware está registrado
echo "3. Verificando registro do middleware:"
if grep -q "CustomCorsMiddleware" ~/bootstrap/app.php; then
    echo "✅ Middleware registrado em bootstrap/app.php"
    grep -A 2 "CustomCorsMiddleware" ~/bootstrap/app.php
else
    echo "❌ Middleware NÃO registrado!"
fi

echo ""

# 4. Limpar cache
echo "4. Limpando cache..."
cd ~
php artisan config:clear
php artisan cache:clear
php artisan route:clear

echo ""

# 5. Testar CORS
echo "5. Testando resposta CORS:"
curl -i -X OPTIONS \
  -H "Origin: https://clownfish-app-rr5rv.ondigitalocean.app" \
  -H "Access-Control-Request-Method: POST" \
  -H "Access-Control-Request-Headers: Content-Type" \
  https://orca-app-7hejo.ondigitalocean.app/api/auth/login 2>&1 | head -20

echo ""
echo "=== FIM DO DIAGNÓSTICO ==="
