#!/bin/bash

# Script de Teste Completo do Sistema
# Data: 06/10/2025

echo "🔍 Testando Sistema Moday..."
echo "================================"
echo ""

# Cores para output
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Função para mostrar resultado
check_result() {
    if [ $1 -eq 0 ]; then
        echo -e "${GREEN}✓${NC} $2"
    else
        echo -e "${RED}✗${NC} $2"
    fi
}

# 1. Verificar Containers Docker
echo "📦 Verificando Containers Docker..."
docker ps --format "table {{.Names}}\t{{.Status}}" | grep backend
echo ""

# 2. Testar Conexão MySQL
echo "🗄️  Testando MySQL..."
docker exec backend-laravel.test-1 php artisan tinker --execute="DB::connection()->getPdo(); echo 'MySQL OK\n';" 2>/dev/null
check_result $? "MySQL Connection"
echo ""

# 3. Testar Conexão Redis
echo "🔴 Testando Redis..."
docker exec backend-laravel.test-1 php artisan tinker --execute="use Illuminate\Support\Facades\Redis; Redis::ping(); echo 'Redis OK\n';" 2>/dev/null
check_result $? "Redis Connection"
echo ""

# 4. Testar Login
echo "🔐 Testando Login..."
LOGIN_RESPONSE=$(curl -s -X POST http://localhost/api/auth/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{"email":"fabio@fabio.com","password":"123456"}')

LOGIN_SUCCESS=$(echo $LOGIN_RESPONSE | jq -r '.success')
if [ "$LOGIN_SUCCESS" = "true" ]; then
    echo -e "${GREEN}✓${NC} Login Successful"
    TOKEN=$(echo $LOGIN_RESPONSE | jq -r '.data.token')
    echo "   Token: ${TOKEN:0:30}..."
else
    echo -e "${RED}✗${NC} Login Failed"
    echo "   Response: $LOGIN_RESPONSE"
fi
echo ""

# 5. Testar Endpoints de Dashboard
if [ "$LOGIN_SUCCESS" = "true" ]; then
    echo "📊 Testando Endpoints de Dashboard..."
    
    # Métricas
    METRICS=$(curl -s -X GET http://localhost/api/dashboard/metrics \
      -H "Authorization: Bearer $TOKEN" \
      -H "Accept: application/json" | jq -r '.success')
    check_result $([ "$METRICS" = "true" ] && echo 0 || echo 1) "Dashboard Metrics"
    
    # Sales Performance
    SALES=$(curl -s -X GET http://localhost/api/dashboard/sales-performance \
      -H "Authorization: Bearer $TOKEN" \
      -H "Accept: application/json" | jq -r '.success')
    check_result $([ "$SALES" = "true" ] && echo 0 || echo 1) "Sales Performance"
    
    # Recent Transactions
    TRANSACTIONS=$(curl -s -X GET http://localhost/api/dashboard/recent-transactions \
      -H "Authorization: Bearer $TOKEN" \
      -H "Accept: application/json" | jq -r '.success')
    check_result $([ "$TRANSACTIONS" = "true" ] && echo 0 || echo 1) "Recent Transactions"
    
    # Top Products
    PRODUCTS=$(curl -s -X GET http://localhost/api/dashboard/top-products \
      -H "Authorization: Bearer $TOKEN" \
      -H "Accept: application/json" | jq -r '.success')
    check_result $([ "$PRODUCTS" = "true" ] && echo 0 || echo 1) "Top Products"
    
    echo ""
fi

# 6. Testar Endpoints de Pedidos
if [ "$LOGIN_SUCCESS" = "true" ]; then
    echo "📋 Testando Endpoints de Pedidos..."
    
    # Listar Pedidos
    ORDERS=$(curl -s -X GET http://localhost/api/orders \
      -H "Authorization: Bearer $TOKEN" \
      -H "Accept: application/json" | jq -r '.success')
    check_result $([ "$ORDERS" = "true" ] && echo 0 || echo 1) "List Orders"
    
    echo ""
fi

# 7. Verificar Reverb (WebSocket)
echo "📡 Verificando Reverb (WebSocket)..."
REVERB_STATUS=$(docker ps --filter "name=backend-reverb-1" --format "{{.Status}}")
if [[ $REVERB_STATUS == *"Up"* ]]; then
    echo -e "${GREEN}✓${NC} Reverb Running"
    echo "   Status: $REVERB_STATUS"
else
    echo -e "${RED}✗${NC} Reverb Not Running"
fi
echo ""

# 8. Verificar Frontend
echo "🎨 Verificando Frontend..."
if lsof -Pi :3000 -sTCP:LISTEN -t >/dev/null ; then
    echo -e "${GREEN}✓${NC} Frontend Running on port 3000"
else
    echo -e "${YELLOW}⚠${NC} Frontend Not Running on port 3000"
    echo "   Run: cd frontend && npm run dev"
fi
echo ""

# 9. Resumo
echo "================================"
echo "📋 Resumo do Teste"
echo "================================"
echo ""
echo "URLs de Acesso:"
echo "  🌐 Frontend: http://localhost:3000"
echo "  🔧 Backend:  http://localhost"
echo "  📡 Reverb:   http://localhost:8080"
echo ""
echo "Credenciais de Teste:"
echo "  📧 Email:    fabio@fabio.com"
echo "  🔑 Senha:    123456"
echo ""
echo "Endpoints Disponíveis:"
echo "  GET /api/dashboard/metrics"
echo "  GET /api/dashboard/sales-performance"
echo "  GET /api/dashboard/recent-transactions"
echo "  GET /api/dashboard/top-products"
echo "  GET /api/orders"
echo ""
echo "✨ Teste Concluído!"
echo ""
