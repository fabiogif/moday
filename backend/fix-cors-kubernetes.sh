#!/bin/bash
# CORS FIX para Digital Ocean App Platform / Kubernetes
# Execute este script dentro do container

echo "🔧 Aplicando correção CORS (Kubernetes/App Platform)..."
echo ""

# Verificar se estamos em um projeto Laravel
if [ ! -f "artisan" ]; then
    echo "❌ Arquivo 'artisan' não encontrado."
    echo "Navegue até o diretório do projeto primeiro:"
    echo "  cd /workspace ou cd /app"
    exit 1
fi

echo "📂 Diretório atual: $(pwd)"
echo ""

# 1. Fazer backup
echo "💾 Criando backups..."
cp public/.htaccess public/.htaccess.backup 2>/dev/null || echo "  .htaccess não encontrado (ok se não usar Apache)"
cp app/Http/Middleware/CustomCorsMiddleware.php app/Http/Middleware/CustomCorsMiddleware.php.backup
cp .env .env.backup

# 2. Remover CORS do .htaccess (se existir)
echo ""
echo "🗑️  Verificando .htaccess..."
if [ -f "public/.htaccess" ]; then
    if grep -q "Access-Control-Allow-Origin" public/.htaccess; then
        sed -i '/<IfModule mod_headers.c>/,/<\/IfModule>/d' public/.htaccess
        echo "✅ CORS removido do .htaccess"
    else
        echo "✅ .htaccess já está limpo"
    fi
else
    echo "ℹ️  .htaccess não existe (normal para Nginx)"
fi

# 3. Atualizar CustomCorsMiddleware
echo ""
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

# 4. Adicionar FRONTEND_URL ao .env
echo ""
echo "🔧 Configurando .env..."
if ! grep -q "FRONTEND_URL=" .env; then
    echo "FRONTEND_URL=https://clownfish-app-rr5rv.ondigitalocean.app" >> .env
    echo "✅ FRONTEND_URL adicionado ao .env"
else
    echo "✅ FRONTEND_URL já existe no .env"
fi

# 5. Limpar cache
echo ""
echo "🧹 Limpando cache do Laravel..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# 6. Otimizar
echo ""
echo "⚡ Otimizando..."
php artisan config:cache
php artisan route:cache

echo ""
echo "=========================================="
echo "✅ CORREÇÃO APLICADA!"
echo "=========================================="
echo ""
echo "📋 O que foi feito:"
echo "  ✓ CustomCorsMiddleware atualizado com URL do frontend"
echo "  ✓ FRONTEND_URL adicionado ao .env"
echo "  ✓ Cache limpo e otimizado"
echo ""
echo "🔄 IMPORTANTE - REINICIAR O POD:"
echo ""
echo "EM KUBERNETES, você precisa reiniciar o pod manualmente."
echo "Existem 2 formas:"
echo ""
echo "OPÇÃO 1 - Via Git Deploy (RECOMENDADO):"
echo "  1. Commit estas mudanças no git"
echo "  2. Push para o repositório"
echo "  3. Digital Ocean App Platform vai fazer redeploy automático"
echo ""
echo "OPÇÃO 2 - Forçar restart do pod (via doctl ou painel):"
echo "  1. Acesse: https://cloud.digitalocean.com/"
echo "  2. Vá em Apps → seu-app → Settings"
echo "  3. Clique em 'Force Rebuild and Deploy'"
echo ""
echo "OPÇÃO 3 - Via kubectl (se tiver acesso):"
echo "  kubectl rollout restart deployment/backend-moday"
echo ""
echo "Após o restart, teste o login no frontend!"
echo ""
