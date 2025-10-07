#!/bin/bash

echo "=========================================="
echo "TESTE DE ESTATÍSTICAS DO DASHBOARD"
echo "=========================================="
echo ""

# Cores para output
GREEN='\033[0;32m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# 1. Login
echo "1. Fazendo login..."
LOGIN_RESPONSE=$(curl -s -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "email": "fabio@fabio.com",
    "password": "123456"
  }')

TOKEN=$(echo "$LOGIN_RESPONSE" | jq -r '.data.token // .token // empty')

if [ -z "$TOKEN" ]; then
  echo -e "${RED}❌ Falha ao obter token${NC}"
  exit 1
fi

echo -e "${GREEN}✅ Login realizado com sucesso${NC}"
echo ""

# 2. Testar métricas do dashboard
echo "2. Testando endpoint /api/dashboard/metrics..."
METRICS=$(curl -s -X GET http://localhost:8000/api/dashboard/metrics \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json")

# Verificar estrutura da resposta
SUCCESS=$(echo "$METRICS" | jq -r '.success')
HAS_DATA=$(echo "$METRICS" | jq -r '.data | length')

echo "   - Resposta success: $SUCCESS"
echo "   - Quantidade de métricas: $HAS_DATA"

if [ "$SUCCESS" = "true" ] && [ "$HAS_DATA" -gt 0 ]; then
  echo -e "${GREEN}✅ Métricas carregadas corretamente${NC}"
  
  # Exibir valores
  REVENUE=$(echo "$METRICS" | jq -r '.data.total_revenue.formatted')
  CLIENTS=$(echo "$METRICS" | jq -r '.data.active_clients.value')
  ORDERS=$(echo "$METRICS" | jq -r '.data.total_orders.value')
  CONVERSION=$(echo "$METRICS" | jq -r '.data.conversion_rate.formatted')
  
  echo ""
  echo "   Valores das estatísticas:"
  echo "   - Receita Total: $REVENUE"
  echo "   - Clientes Ativos: $CLIENTS"
  echo "   - Total de Pedidos: $ORDERS"
  echo "   - Taxa de Conversão: $CONVERSION"
else
  echo -e "${RED}❌ Erro ao carregar métricas${NC}"
fi

echo ""

# 3. Testar produtos principais
echo "3. Testando endpoint /api/dashboard/top-products..."
PRODUCTS=$(curl -s -X GET http://localhost:8000/api/dashboard/top-products \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json")

PRODUCTS_COUNT=$(echo "$PRODUCTS" | jq -r '.data.products | length')
echo "   - Quantidade de produtos: $PRODUCTS_COUNT"

if [ "$PRODUCTS_COUNT" -gt 0 ]; then
  echo -e "${GREEN}✅ Produtos principais carregados${NC}"
  TOP_PRODUCT=$(echo "$PRODUCTS" | jq -r '.data.products[0].name')
  echo "   - Produto #1: $TOP_PRODUCT"
else
  echo -e "${RED}❌ Nenhum produto encontrado${NC}"
fi

echo ""

# 4. Testar transações recentes
echo "4. Testando endpoint /api/dashboard/recent-transactions..."
TRANSACTIONS=$(curl -s -X GET http://localhost:8000/api/dashboard/recent-transactions \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json")

TRANS_COUNT=$(echo "$TRANSACTIONS" | jq -r '.data.transactions | length')
echo "   - Quantidade de transações: $TRANS_COUNT"

if [ "$TRANS_COUNT" -gt 0 ]; then
  echo -e "${GREEN}✅ Transações recentes carregadas${NC}"
  FIRST_TRANS=$(echo "$TRANSACTIONS" | jq -r '.data.transactions[0].identify')
  echo "   - Transação #1: $FIRST_TRANS"
else
  echo -e "${RED}❌ Nenhuma transação encontrada${NC}"
fi

echo ""

# 5. Testar performance de vendas
echo "5. Testando endpoint /api/dashboard/sales-performance..."
SALES=$(curl -s -X GET http://localhost:8000/api/dashboard/sales-performance \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json")

SALES_DATA=$(echo "$SALES" | jq -r '.data.monthly_data | length')
echo "   - Quantidade de meses com dados: $SALES_DATA"

if [ "$SALES_DATA" -gt 0 ]; then
  echo -e "${GREEN}✅ Performance de vendas carregada${NC}"
  CURRENT_SALES=$(echo "$SALES" | jq -r '.data.current_month.sales')
  echo "   - Vendas do mês atual: R$ $CURRENT_SALES"
else
  echo -e "${RED}❌ Nenhum dado de vendas encontrado${NC}"
fi

echo ""
echo "=========================================="
echo -e "${GREEN}✅ TODOS OS TESTES CONCLUÍDOS${NC}"
echo "=========================================="
