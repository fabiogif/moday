#!/bin/bash

echo "🧪 Testando Sistema de Cache Redis"
echo "=================================="
echo ""

# Colors
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# 1. Testar conexão Redis
echo "1. Testando conexão com Redis..."
./vendor/bin/sail artisan cache:test
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Redis conectado com sucesso!${NC}"
else
    echo -e "${RED}✗ Falha ao conectar com Redis${NC}"
    exit 1
fi
echo ""

# 2. Limpar cache
echo "2. Limpando cache..."
./vendor/bin/sail artisan cache:clear
echo -e "${GREEN}✓ Cache limpo${NC}"
echo ""

# 3. Verificar rotas do dashboard
echo "3. Verificando rotas do dashboard..."
./vendor/bin/sail artisan route:list --path=dashboard 2>&1 | head -10
echo ""

# 4. Testar invalidação de cache
echo "4. Testando invalidação de cache para tenant 1..."
./vendor/bin/sail artisan cache:clear-tenant 1
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Cache do tenant invalidado com sucesso!${NC}"
else
    echo -e "${RED}✗ Falha ao invalidar cache${NC}"
fi
echo ""

# 5. Verificar status dos containers
echo "5. Status dos containers:"
./vendor/bin/sail ps | grep -E "redis|laravel"
echo ""

echo -e "${GREEN}=================================="
echo "✓ Todos os testes concluídos!"
echo -e "==================================${NC}"
