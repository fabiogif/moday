#!/bin/bash

echo "🚀 Deploy da correção CORS para produção"
echo "========================================"
echo ""

# Verificar se estamos no diretório correto
if [ ! -f "artisan" ]; then
    echo "❌ Erro: Execute este script no diretório raiz do projeto Laravel"
    exit 1
fi

echo "📋 Arquivos que serão atualizados:"
echo "  - bootstrap/app.php"
echo "  - app/Http/Middleware/CustomCorsMiddleware.php" 
echo "  - app/Http/Kernel.php"
echo ""

# Fazer backup dos arquivos atuais
echo "💾 Fazendo backup dos arquivos atuais..."
cp bootstrap/app.php bootstrap/app.php.backup.$(date +%Y%m%d_%H%M%S)
cp app/Http/Middleware/CustomCorsMiddleware.php app/Http/Middleware/CustomCorsMiddleware.php.backup.$(date +%Y%m%d_%H%M%S)
cp app/Http/Kernel.php app/Http/Kernel.php.backup.$(date +%Y%m%d_%H%M%S)
echo "✅ Backups criados com sucesso!"
echo ""

# Limpar caches
echo "🧹 Limpando caches..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
echo "✅ Caches limpos!"
echo ""

# Recriar cache de configuração
echo "⚙️ Recriando cache de configuração..."
php artisan config:cache
echo "✅ Cache de configuração recriado!"
echo ""

# Testar configuração
echo "🧪 Testando configuração CORS..."
echo "Frontend: https://moday-nine.vercel.app"
echo "Backend: https://orca-app-7hejo.ondigitalocean.app"
echo ""

# Teste de preflight
echo "📡 Testando requisição OPTIONS..."
RESPONSE=$(curl -s -X OPTIONS "https://orca-app-7hejo.ondigitalocean.app/api/auth/login" \
  -H "Origin: https://moday-nine.vercel.app" \
  -H "Access-Control-Request-Method: POST" \
  -H "Access-Control-Request-Headers: Content-Type,Authorization" \
  -I)

if echo "$RESPONSE" | grep -q "access-control-allow-origin: https://moday-nine.vercel.app"; then
    echo "✅ CORS configurado corretamente!"
    echo "✅ O header Access-Control-Allow-Origin está definido corretamente"
else
    echo "⚠️ Ainda pode haver problemas com CORS"
    echo "📋 Headers retornados:"
    echo "$RESPONSE" | grep -i "access-control"
fi

echo ""
echo "🎯 Deploy concluído!"
echo ""
echo "📝 Próximos passos:"
echo "  1. Teste o login no frontend"
echo "  2. Se ainda houver problemas, reinicie o servidor web"
echo "  3. Verifique os logs do servidor se necessário"
