#!/bin/bash

# Script para ser executado NO SERVIDOR DE PRODUÇÃO
# Copia este script para o servidor e execute-o lá

echo "🚀 Executando Deploy de Correção CORS no Servidor"
echo "=================================================="
echo ""

# Cores
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Verificar se está rodando como root ou com sudo
if [ "$EUID" -ne 0 ]; then 
    echo -e "${RED}❌ Este script precisa ser executado como root ou com sudo${NC}"
    exit 1
fi

# Diretório da aplicação
APP_DIR="/var/www/html"
TIMESTAMP=$(date +%Y%m%d_%H%M%S)

echo -e "${BLUE}📂 Diretório da aplicação: ${APP_DIR}${NC}"
echo ""

# Verificar se o diretório existe
if [ ! -d "$APP_DIR" ]; then
    echo -e "${RED}❌ Diretório da aplicação não encontrado: ${APP_DIR}${NC}"
    exit 1
fi

cd "$APP_DIR" || exit 1

echo -e "${YELLOW}💾 Passo 1/9: Criando backups...${NC}"
mkdir -p backups/cors_fix_${TIMESTAMP}
cp bootstrap/app.php backups/cors_fix_${TIMESTAMP}/app.php.bak 2>/dev/null || echo "  Arquivo bootstrap/app.php não encontrado para backup"
cp app/Http/Middleware/CustomCorsMiddleware.php backups/cors_fix_${TIMESTAMP}/CustomCorsMiddleware.php.bak 2>/dev/null || echo "  Arquivo CustomCorsMiddleware.php não encontrado para backup"
echo -e "${GREEN}✅ Backups criados em: backups/cors_fix_${TIMESTAMP}/${NC}"
echo ""

echo -e "${YELLOW}🔄 Passo 2/9: Verificando status do Git...${NC}"
git status
echo ""

echo -e "${YELLOW}📥 Passo 3/9: Atualizando código do repositório...${NC}"
git pull origin main
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Código atualizado com sucesso${NC}"
else
    echo -e "${RED}❌ Erro ao atualizar código${NC}"
    echo "Verifique se há conflitos ou problemas de permissão"
    exit 1
fi
echo ""

echo -e "${YELLOW}🧹 Passo 4/9: Limpando caches...${NC}"
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
echo -e "${GREEN}✅ Caches limpos${NC}"
echo ""

echo -e "${YELLOW}⚙️ Passo 5/9: Recriando cache de configuração...${NC}"
php artisan config:cache
echo -e "${GREEN}✅ Cache de configuração recriado${NC}"
echo ""

echo -e "${YELLOW}🔐 Passo 6/9: Ajustando permissões...${NC}"
chown -R www-data:www-data storage bootstrap/cache
chmod -R 775 storage bootstrap/cache
echo -e "${GREEN}✅ Permissões ajustadas${NC}"
echo ""

echo -e "${YELLOW}🔄 Passo 7/9: Detectando versão do PHP...${NC}"
PHP_VERSION=$(php -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;")
echo "  Versão do PHP: ${PHP_VERSION}"
echo ""

echo -e "${YELLOW}🔄 Passo 8/9: Reiniciando serviços...${NC}"

# Tentar reiniciar PHP-FPM
if systemctl list-units --type=service | grep -q "php${PHP_VERSION}-fpm"; then
    systemctl restart php${PHP_VERSION}-fpm
    echo -e "${GREEN}✅ PHP ${PHP_VERSION}-FPM reiniciado${NC}"
else
    echo -e "${YELLOW}⚠️ Serviço php${PHP_VERSION}-fpm não encontrado, tentando outras versões...${NC}"
    
    for version in 8.3 8.2 8.1 8.0 7.4; do
        if systemctl list-units --type=service | grep -q "php${version}-fpm"; then
            systemctl restart php${version}-fpm
            echo -e "${GREEN}✅ PHP ${version}-FPM reiniciado${NC}"
            break
        fi
    done
fi

# Reiniciar Nginx
if systemctl list-units --type=service | grep -q "nginx"; then
    systemctl restart nginx
    echo -e "${GREEN}✅ Nginx reiniciado${NC}"
else
    echo -e "${YELLOW}⚠️ Nginx não encontrado${NC}"
fi

echo ""

echo -e "${YELLOW}🧪 Passo 9/9: Testando configuração CORS...${NC}"
echo ""

# Testar CORS
CORS_TEST=$(curl -s -o /dev/null -w "%{http_code}" -X OPTIONS https://orca-app-7hejo.ondigitalocean.app/api/login \
  -H "Origin: https://moday-nine.vercel.app" \
  -H "Access-Control-Request-Method: POST" \
  -H "Access-Control-Request-Headers: Content-Type, Authorization")

if [ "$CORS_TEST" = "200" ]; then
    echo -e "${GREEN}✅ Teste CORS OPTIONS: Sucesso (HTTP 200)${NC}"
else
    echo -e "${RED}❌ Teste CORS OPTIONS: Falhou (HTTP ${CORS_TEST})${NC}"
fi

echo ""
echo -e "${BLUE}📋 Verificando headers CORS retornados:${NC}"
curl -s -X OPTIONS https://orca-app-7hejo.ondigitalocean.app/api/login \
  -H "Origin: https://moday-nine.vercel.app" \
  -H "Access-Control-Request-Method: POST" \
  -H "Access-Control-Request-Headers: Content-Type, Authorization" \
  -D - -o /dev/null | grep -i "access-control"

echo ""
echo -e "${GREEN}🎉 Deploy concluído!${NC}"
echo ""
echo -e "${YELLOW}📝 Checklist de verificação:${NC}"
echo "  1. Verifique se os headers CORS estão corretos acima"
echo "  2. Teste o login no frontend: https://moday-nine.vercel.app"
echo "  3. Monitore os logs por alguns minutos:"
echo "     - tail -f storage/logs/laravel.log"
echo "     - tail -f /var/log/nginx/error.log"
echo ""
echo -e "${YELLOW}💡 Em caso de problemas:${NC}"
echo "  - Os backups estão em: ${APP_DIR}/backups/cors_fix_${TIMESTAMP}/"
echo "  - Para restaurar: cp backups/cors_fix_${TIMESTAMP}/*.bak ./caminho/original"
echo ""
echo -e "${BLUE}🔗 URLs da aplicação:${NC}"
echo "  Backend:  https://orca-app-7hejo.ondigitalocean.app"
echo "  Frontend: https://moday-nine.vercel.app"
echo ""
