#!/bin/bash

# Teste de autenticação e carregamento do dashboard

echo "=== Teste de Login e Dashboard ==="
echo ""

# 1. Login
echo "1. Fazendo login..."
LOGIN_RESPONSE=$(curl -s -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d '{
    "email": "fabio@fabio.com",
    "password": "123456"
  }')

echo "Resposta do login:"
echo "$LOGIN_RESPONSE" | jq '.'

# Extrair token
TOKEN=$(echo "$LOGIN_RESPONSE" | jq -r '.data.token // .token // empty')

if [ -z "$TOKEN" ]; then
  echo "❌ Falha ao obter token"
  exit 1
fi

echo ""
echo "✅ Token obtido: ${TOKEN:0:50}..."
echo ""

# 2. Testar endpoint de métricas
echo "2. Carregando métricas do dashboard..."
METRICS=$(curl -s -X GET http://localhost:8000/api/dashboard/metrics \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json")

echo "Métricas:"
echo "$METRICS" | jq '.'
echo ""

# 3. Testar produtos principais
echo "3. Carregando produtos principais..."
PRODUCTS=$(curl -s -X GET http://localhost:8000/api/dashboard/top-products \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json")

echo "Produtos:"
echo "$PRODUCTS" | jq '.data.products'
echo ""

# 4. Testar transações recentes
echo "4. Carregando transações recentes..."
TRANSACTIONS=$(curl -s -X GET http://localhost:8000/api/dashboard/recent-transactions \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json")

echo "Transações:"
echo "$TRANSACTIONS" | jq '.data.transactions'
echo ""

# 5. Testar performance de vendas
echo "5. Carregando performance de vendas..."
SALES=$(curl -s -X GET http://localhost:8000/api/dashboard/sales-performance \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json")

echo "Performance de vendas:"
echo "$SALES" | jq '.data.current_month'
echo ""

echo "=== Teste concluído ==="
