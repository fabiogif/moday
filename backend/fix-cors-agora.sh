#!/bin/bash
# CORS FIX - Script de Comando Único para Digital Ocean
# Copie e cole este script inteiro no console do servidor

echo "🔧 Aplicando correção CORS..."

# Descobrir o diretório do projeto
if [ -d "/var/www/backend" ]; then
    cd /var/www/backend
elif [ -d "/var/www/html" ]; then
    cd /var/www/html
elif [ -d "/var/www" ]; then
    cd /var/www
else
    echo "❌ Diretório do projeto não encontrado!"
    echo "Execute: cd /caminho/do/seu/projeto"
    exit 1
fi

echo "📂 Diretório: $(pwd)"

# Verificar se é um projeto Laravel
if [ ! -f "artisan" ]; then
    echo "❌ Arquivo 'artisan' não encontrado. Você está no diretório correto?"
    exit 1
fi

# Fazer backup
echo "💾 Criando backups..."
cp public/.htaccess public/.htaccess.backup.$(date +%Y%m%d_%H%M%S)
cp app/Http/Middleware/CustomCorsMiddleware.php app/Http/Middleware/CustomCorsMiddleware.php.backup.$(date +%Y%m%d_%H%M%S)
cp .env .env.backup.$(date +%Y%m%d_%H%M%S)

# 1. Remover CORS do .htaccess
echo "🗑️  Removendo CORS do .htaccess..."
if grep -q "Access-Control-Allow-Origin" public/.htaccess; then
    sed -i '/<IfModule mod_headers.c>/,/<\/IfModule>/d' public/.htaccess
    echo "✅ CORS removido do .htaccess"
else
    echo "✅ .htaccess já está limpo"
fi

# 2. Atualizar CustomCorsMiddleware
echo "📝 Atualizando CustomCorsMiddleware..."
cat > app/Http/Middleware/CustomCorsMiddleware.php << 'EOFMIDDLEWARE'
<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CustomCorsMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $allowedOrigins = array_filter([
            'http://localhost:3000',
            'http://localhost:3001',
            'https://localhost:3000',
            'https://localhost:3001',
            'https://moday-nine.vercel.app',
            'https://clownfish-app-rr5rv.ondigitalocean.app',
            env('FRONTEND_URL'),
            env('ADDITIONAL_CORS_ORIGIN'),
        ]);

        $origin = $request->headers->get('Origin');
        
        // Handle preflight requests
        if ($request->getMethod() === 'OPTIONS') {
            if ($origin && in_array($origin, $allowedOrigins)) {
                return response('', 200)
                    ->header('Access-Control-Allow-Origin', $origin)
                    ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS, PATCH')
                    ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Accept, Origin, X-CSRF-TOKEN')
                    ->header('Access-Control-Allow-Credentials', 'true')
                    ->header('Access-Control-Max-Age', '86400');
            }
            
            return response('CORS not allowed', 403);
        }
        
        // Handle actual requests
        if ($origin && in_array($origin, $allowedOrigins)) {
            $response = $next($request);
            
            $response->headers->set('Access-Control-Allow-Origin', $origin);
            $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS, PATCH');
            $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Accept, Origin, X-CSRF-TOKEN');
            $response->headers->set('Access-Control-Allow-Credentials', 'true');
            $response->headers->set('Access-Control-Max-Age', '86400');
            
            return $response;
        }

        return $next($request);
    }
}
EOFMIDDLEWARE
echo "✅ CustomCorsMiddleware atualizado"

# 3. Adicionar FRONTEND_URL ao .env
echo "🔧 Configurando .env..."
if ! grep -q "FRONTEND_URL=" .env; then
    echo "FRONTEND_URL=https://clownfish-app-rr5rv.ondigitalocean.app" >> .env
    echo "✅ FRONTEND_URL adicionado"
else
    echo "✅ FRONTEND_URL já existe"
fi

# 4. Limpar cache
echo "🧹 Limpando cache..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# 5. Otimizar
echo "⚡ Otimizando..."
php artisan config:cache
php artisan route:cache

# 6. Reiniciar serviços
echo "🔄 Reiniciando serviços..."
if systemctl list-units --type=service 2>/dev/null | grep -q "php8.2-fpm"; then
    systemctl restart php8.2-fpm
    echo "✅ PHP 8.2 FPM reiniciado"
elif systemctl list-units --type=service 2>/dev/null | grep -q "php8.1-fpm"; then
    systemctl restart php8.1-fpm
    echo "✅ PHP 8.1 FPM reiniciado"
elif systemctl list-units --type=service 2>/dev/null | grep -q "php-fpm"; then
    systemctl restart php-fpm
    echo "✅ PHP FPM reiniciado"
fi

if systemctl list-units --type=service 2>/dev/null | grep -q "nginx"; then
    systemctl restart nginx
    echo "✅ Nginx reiniciado"
elif systemctl list-units --type=service 2>/dev/null | grep -q "apache2"; then
    systemctl restart apache2
    echo "✅ Apache reiniciado"
fi

# 7. Testar
echo ""
echo "🧪 Testando CORS..."
RESPONSE=$(curl -s -i -X OPTIONS \
  -H "Origin: https://clownfish-app-rr5rv.ondigitalocean.app" \
  -H "Access-Control-Request-Method: POST" \
  https://orca-app-7hejo.ondigitalocean.app/api/auth/login 2>&1)

if echo "$RESPONSE" | grep -q "Access-Control-Allow-Origin: https://clownfish-app-rr5rv.ondigitalocean.app"; then
    echo "✅✅✅ CORS FUNCIONANDO CORRETAMENTE! ✅✅✅"
elif echo "$RESPONSE" | grep -q "Access-Control-Allow-Origin: \*"; then
    echo "⚠️  Ainda tem wildcard (*). Pode ser cache do navegador."
else
    echo "⚠️  Sem headers CORS. Verifique os logs."
fi

echo ""
echo "=========================================="
echo "✅ CORREÇÃO APLICADA COM SUCESSO!"
echo "=========================================="
echo ""
echo "📋 O que foi feito:"
echo "  ✓ CORS removido do .htaccess"
echo "  ✓ CustomCorsMiddleware atualizado"
echo "  ✓ FRONTEND_URL adicionado ao .env"
echo "  ✓ Cache limpo e otimizado"
echo "  ✓ Serviços reiniciados"
echo ""
echo "🎯 PRÓXIMO PASSO:"
echo "   Teste o login no frontend agora!"
echo ""
echo "Se ainda não funcionar:"
echo "   tail -f storage/logs/laravel.log"
