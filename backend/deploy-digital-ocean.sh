#!/bin/bash

# Deploy CORS Fix to Digital Ocean
# Este script envia os arquivos diretamente para o servidor e executa o deploy

echo "=========================================="
echo "DEPLOY CORS FIX - DIGITAL OCEAN"
echo "=========================================="
echo ""

# Verificar se temos as credenciais SSH
if [ ! -f "id_ed25519" ]; then
    echo "❌ Arquivo de chave SSH não encontrado!"
    echo "Por favor, configure as credenciais SSH para o servidor Digital Ocean"
    exit 1
fi

# Configurar variáveis
REMOTE_USER="root"  # Ajuste se necessário
REMOTE_HOST="orca-app-7hejo.ondigitalocean.app"
REMOTE_PATH="/var/www/backend"  # Ajuste para o caminho correto no servidor

echo "📋 Configuração:"
echo "   Servidor: $REMOTE_HOST"
echo "   Usuário: $REMOTE_USER"
echo "   Caminho remoto: $REMOTE_PATH"
echo ""

# Confirmar deploy
read -p "Continuar com o deploy? (s/n) " -n 1 -r
echo ""
if [[ ! $REPLY =~ ^[SsYy]$ ]]; then
    echo "Deploy cancelado."
    exit 1
fi

echo ""
echo "🚀 Iniciando deploy..."
echo ""

# 1. Enviar arquivos modificados
echo "1️⃣  Enviando arquivos para o servidor..."

# Enviar CustomCorsMiddleware.php
scp -i id_ed25519 app/Http/Middleware/CustomCorsMiddleware.php \
    $REMOTE_USER@$REMOTE_HOST:$REMOTE_PATH/app/Http/Middleware/

# Enviar .htaccess
scp -i id_ed25519 public/.htaccess \
    $REMOTE_USER@$REMOTE_HOST:$REMOTE_PATH/public/

# Enviar scripts de diagnóstico
scp -i id_ed25519 deploy-to-production.sh diagnose-cors.sh \
    $REMOTE_USER@$REMOTE_HOST:$REMOTE_PATH/

echo "✅ Arquivos enviados!"
echo ""

# 2. Executar comandos no servidor
echo "2️⃣  Executando comandos no servidor..."
echo ""

ssh -i id_ed25519 $REMOTE_USER@$REMOTE_HOST << 'ENDSSH'
cd /var/www/backend

echo "📝 Atualizando .env..."
# Adicionar FRONTEND_URL se não existir
if ! grep -q "FRONTEND_URL=" .env; then
    echo "FRONTEND_URL=https://clownfish-app-rr5rv.ondigitalocean.app" >> .env
    echo "✅ FRONTEND_URL adicionado ao .env"
else
    echo "✅ FRONTEND_URL já existe no .env"
fi

echo ""
echo "🧹 Limpando cache do Laravel..."
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

echo ""
echo "⚡ Otimizando..."
php artisan config:cache
php artisan route:cache

echo ""
echo "🔄 Reiniciando serviços..."
# Tentar reiniciar PHP-FPM
if systemctl list-units --type=service | grep -q "php8.2-fpm"; then
    systemctl restart php8.2-fpm
    echo "✅ PHP 8.2 FPM reiniciado"
elif systemctl list-units --type=service | grep -q "php8.1-fpm"; then
    systemctl restart php8.1-fpm
    echo "✅ PHP 8.1 FPM reiniciado"
elif systemctl list-units --type=service | grep -q "php-fpm"; then
    systemctl restart php-fpm
    echo "✅ PHP FPM reiniciado"
fi

# Reiniciar Nginx
if systemctl list-units --type=service | grep -q "nginx"; then
    systemctl restart nginx
    echo "✅ Nginx reiniciado"
fi

echo ""
echo "🧪 Testando CORS..."
curl -i -X OPTIONS \
  -H "Origin: https://clownfish-app-rr5rv.ondigitalocean.app" \
  -H "Access-Control-Request-Method: POST" \
  -H "Access-Control-Request-Headers: Content-Type, Authorization" \
  https://orca-app-7hejo.ondigitalocean.app/api/auth/login 2>&1 | head -20

echo ""
echo "📋 Verificando arquivos..."
echo ""
echo "CustomCorsMiddleware.php contém a URL de produção:"
grep -c "clownfish-app-rr5rv.ondigitalocean.app" app/Http/Middleware/CustomCorsMiddleware.php && echo "✅ SIM" || echo "❌ NÃO"

echo ""
echo ".htaccess está limpo (sem CORS):"
grep -c "Access-Control-Allow-Origin" public/.htaccess && echo "❌ AINDA TEM CORS" || echo "✅ LIMPO"

echo ""
echo "FRONTEND_URL no .env:"
grep "FRONTEND_URL" .env

echo ""
echo "=========================================="
echo "✅ DEPLOY CONCLUÍDO!"
echo "=========================================="
echo ""
echo "Agora teste o login no frontend."
echo "Se ainda não funcionar, execute:"
echo "  ssh -i id_ed25519 root@orca-app-7hejo.ondigitalocean.app"
echo "  cd /var/www/backend"
echo "  ./diagnose-cors.sh"
echo "  tail -f storage/logs/laravel.log | grep CORS"
ENDSSH

echo ""
echo "✅ Deploy finalizado com sucesso!"
echo ""
echo "Próximo passo: Teste o login no frontend agora!"
