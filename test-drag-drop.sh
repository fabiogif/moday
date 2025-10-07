#!/bin/bash

# Script de teste para funcionalidade de arrastar pedidos
echo "======================================"
echo "Teste: Funcionalidade Arrastar Pedidos"
echo "======================================"
echo ""

# Cores para output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# 1. Verificar se as dependências estão instaladas
echo -e "${YELLOW}1. Verificando dependências do frontend...${NC}"
cd frontend

if ! command -v npm &> /dev/null; then
    echo -e "${RED}❌ npm não encontrado${NC}"
    exit 1
fi

echo -e "${GREEN}✓ npm encontrado${NC}"

# Verificar se node_modules existe
if [ ! -d "node_modules" ]; then
    echo -e "${YELLOW}⚠ node_modules não encontrado. Executando npm install...${NC}"
    npm install
fi

echo -e "${GREEN}✓ node_modules presente${NC}"

# 2. Verificar se as bibliotecas de drag-and-drop estão instaladas
echo ""
echo -e "${YELLOW}2. Verificando bibliotecas de drag-and-drop...${NC}"

PACKAGES=(
    "@dnd-kit/core"
    "@dnd-kit/sortable"
    "@dnd-kit/utilities"
    "laravel-echo"
    "pusher-js"
)

for package in "${PACKAGES[@]}"; do
    if grep -q "\"$package\"" package.json; then
        echo -e "${GREEN}✓ $package instalado${NC}"
    else
        echo -e "${RED}❌ $package NÃO encontrado${NC}"
    fi
done

# 3. Verificar se os arquivos necessários existem
echo ""
echo -e "${YELLOW}3. Verificando arquivos da funcionalidade...${NC}"

FILES=(
    "src/app/(dashboard)/orders/board/page.tsx"
    "src/hooks/use-realtime.ts"
    "src/lib/echo.ts"
)

for file in "${FILES[@]}"; do
    if [ -f "$file" ]; then
        echo -e "${GREEN}✓ $file existe${NC}"
    else
        echo -e "${RED}❌ $file NÃO encontrado${NC}"
    fi
done

# 4. Verificar variáveis de ambiente
echo ""
echo -e "${YELLOW}4. Verificando variáveis de ambiente...${NC}"

if [ -f ".env.local" ] || [ -f ".env" ]; then
    echo -e "${GREEN}✓ Arquivo .env encontrado${NC}"
    
    ENV_FILE=".env.local"
    [ ! -f "$ENV_FILE" ] && ENV_FILE=".env"
    
    ENV_VARS=(
        "NEXT_PUBLIC_API_URL"
        "NEXT_PUBLIC_REVERB_APP_KEY"
        "NEXT_PUBLIC_REVERB_HOST"
        "NEXT_PUBLIC_REVERB_PORT"
    )
    
    for var in "${ENV_VARS[@]}"; do
        if grep -q "^$var=" "$ENV_FILE"; then
            value=$(grep "^$var=" "$ENV_FILE" | cut -d '=' -f2-)
            echo -e "${GREEN}✓ $var = $value${NC}"
        else
            echo -e "${YELLOW}⚠ $var não configurado${NC}"
        fi
    done
else
    echo -e "${RED}❌ Arquivo .env não encontrado${NC}"
fi

# 5. Verificar TypeScript
echo ""
echo -e "${YELLOW}5. Verificando erros de TypeScript...${NC}"
npx tsc --noEmit --skipLibCheck 2>&1 | head -20

# 6. Build do projeto
echo ""
echo -e "${YELLOW}6. Testando build do projeto...${NC}"
npm run build > /dev/null 2>&1

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Build concluído com sucesso${NC}"
else
    echo -e "${RED}❌ Build falhou${NC}"
    echo "Execute 'npm run build' para ver os erros"
fi

# 7. Verificar backend
echo ""
echo -e "${YELLOW}7. Verificando backend Laravel...${NC}"
cd ../backend

if [ -f "artisan" ]; then
    echo -e "${GREEN}✓ Laravel encontrado${NC}"
    
    # Verificar se o OrderApiController existe
    if [ -f "app/Http/Controllers/Api/OrderApiController.php" ]; then
        echo -e "${GREEN}✓ OrderApiController encontrado${NC}"
        
        # Verificar método update
        if grep -q "function update" "app/Http/Controllers/Api/OrderApiController.php"; then
            echo -e "${GREEN}✓ Método update() encontrado${NC}"
        else
            echo -e "${RED}❌ Método update() NÃO encontrado${NC}"
        fi
    else
        echo -e "${RED}❌ OrderApiController NÃO encontrado${NC}"
    fi
    
    # Verificar OrderService
    if [ -f "app/Services/OrderService.php" ]; then
        echo -e "${GREEN}✓ OrderService encontrado${NC}"
    else
        echo -e "${RED}❌ OrderService NÃO encontrado${NC}"
    fi
else
    echo -e "${RED}❌ Laravel não encontrado${NC}"
fi

# Resumo final
echo ""
echo "======================================"
echo -e "${GREEN}Teste concluído!${NC}"
echo "======================================"
echo ""
echo "Para testar a funcionalidade:"
echo "1. Inicie o backend: cd backend && php artisan serve"
echo "2. (Opcional) Inicie o Reverb: cd backend && php artisan reverb:start"
echo "3. Inicie o frontend: cd frontend && npm run dev"
echo "4. Acesse: http://localhost:3000/orders/board"
echo "5. Arraste os pedidos entre as colunas!"
echo ""
