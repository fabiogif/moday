#!/bin/bash

echo "🚀 Deploy de Correção CORS para Produção"
echo "=========================================="
echo ""

# Cores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Variáveis de ambiente
PRODUCTION_URL="orca-app-7hejo.ondigitalocean.app"
FRONTEND_URL="https://moday-nine.vercel.app"

echo -e "${YELLOW}📋 Verificando alterações locais...${NC}"
if ! git diff-index --quiet HEAD --; then
    echo -e "${RED}❌ Existem alterações não commitadas!${NC}"
    echo "Por favor, commit suas alterações antes de fazer deploy."
    exit 1
fi

echo -e "${GREEN}✅ Nenhuma alteração pendente${NC}"
echo ""

echo -e "${YELLOW}🔄 Sincronizando com repositório remoto...${NC}"
git pull origin main

echo ""
echo -e "${YELLOW}📤 Enviando alterações para produção...${NC}"
git push origin main

echo ""
echo -e "${YELLOW}⚙️ Instruções para deploy no servidor DigitalOcean:${NC}"
echo ""
echo "Execute os seguintes comandos no servidor de produção:"
echo ""
echo -e "${GREEN}# 1. Conectar ao servidor${NC}"
echo "   ssh root@${PRODUCTION_URL}"
echo ""
echo -e "${GREEN}# 2. Navegar para o diretório da aplicação${NC}"
echo "   cd /var/www/html"
echo ""
echo -e "${GREEN}# 3. Fazer backup dos arquivos atuais${NC}"
echo "   cp bootstrap/app.php bootstrap/app.php.bak"
echo "   cp app/Http/Middleware/CustomCorsMiddleware.php app/Http/Middleware/CustomCorsMiddleware.php.bak"
echo ""
echo -e "${GREEN}# 4. Atualizar código do repositório${NC}"
echo "   git pull origin main"
echo ""
echo -e "${GREEN}# 5. Limpar caches${NC}"
echo "   php artisan config:clear"
echo "   php artisan cache:clear"
echo "   php artisan route:clear"
echo "   php artisan view:clear"
echo ""
echo -e "${GREEN}# 6. Recriar cache de configuração${NC}"
echo "   php artisan config:cache"
echo ""
echo -e "${GREEN}# 7. Ajustar permissões${NC}"
echo "   chown -R www-data:www-data storage bootstrap/cache"
echo "   chmod -R 775 storage bootstrap/cache"
echo ""
echo -e "${GREEN}# 8. Reiniciar serviços (se necessário)${NC}"
echo "   systemctl restart php8.3-fpm  # ou php8.2-fpm, dependendo da versão"
echo "   systemctl restart nginx"
echo ""
echo -e "${YELLOW}🧪 Teste a configuração CORS após o deploy:${NC}"
echo ""
echo "curl -X OPTIONS https://${PRODUCTION_URL}/api/login \\"
echo "  -H \"Origin: ${FRONTEND_URL}\" \\"
echo "  -H \"Access-Control-Request-Method: POST\" \\"
echo "  -H \"Access-Control-Request-Headers: Content-Type, Authorization\" \\"
echo "  -v"
echo ""
echo -e "${YELLOW}📝 Checklist de verificação:${NC}"
echo "  ✓ Verificar se os headers CORS estão corretos"
echo "  ✓ Testar login no frontend (${FRONTEND_URL})"
echo "  ✓ Verificar logs de erro: tail -f storage/logs/laravel.log"
echo "  ✓ Verificar logs do nginx: tail -f /var/log/nginx/error.log"
echo ""
echo -e "${GREEN}✅ Preparação concluída!${NC}"
echo ""
echo -e "${YELLOW}💡 Dica:${NC} Se ainda houver problemas com CORS após o deploy,"
echo "   verifique se o .env de produção contém:"
echo ""
echo "   APP_URL=https://${PRODUCTION_URL}"
echo "   FRONTEND_URL=${FRONTEND_URL}"
echo "   SANCTUM_STATEFUL_DOMAINS=moday-nine.vercel.app"
echo "   SESSION_DOMAIN=.${PRODUCTION_URL}"
echo ""
