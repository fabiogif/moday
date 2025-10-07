#!/bin/bash

echo "=========================================="
echo "TESTE: CONSULTA DE PEDIDOS DO CLIENTE"
echo "=========================================="
echo ""

GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m'

TENANT_SLUG="empresa-dev"
API_URL="http://localhost:8000/api/store/${TENANT_SLUG}"

# Criar cliente de teste
TEST_EMAIL="cliente.pedidos.$(date +%s)@teste.com"
TEST_PASSWORD="senha123"

echo -e "${YELLOW}1. Criando cliente de teste...${NC}"

REGISTER_DATA=$(cat <<EOF
{
  "name": "Cliente Teste Pedidos",
  "email": "$TEST_EMAIL",
  "password": "$TEST_PASSWORD",
  "password_confirmation": "$TEST_PASSWORD",
  "phone": "11987654321",
  "cpf": "12345678901"
}
EOF
)

REGISTER_RESPONSE=$(curl -s -X POST "${API_URL}/auth/register" \
  -H "Content-Type: application/json" \
  -d "$REGISTER_DATA")

TOKEN=$(echo "$REGISTER_RESPONSE" | jq -r '.data.token')

if [ ! -z "$TOKEN" ] && [ "$TOKEN" != "null" ]; then
    echo -e "   ${GREEN}✓ Cliente criado e autenticado${NC}"
    echo "   Token: ${TOKEN:0:30}..."
else
    echo -e "   ${RED}✗ Erro ao criar cliente${NC}"
    exit 1
fi

echo ""
echo -e "${YELLOW}2. Criando pedido de teste...${NC}"

# Buscar produto
PRODUCT_UUID=$(curl -s "${API_URL}/products" | jq -r '.data[0].uuid')

if [ -z "$PRODUCT_UUID" ] || [ "$PRODUCT_UUID" == "null" ]; then
    echo -e "   ${RED}✗ Nenhum produto disponível${NC}"
    exit 1
fi

# Criar pedido
ORDER_DATA=$(cat <<EOF
{
  "client": {
    "name": "Cliente Teste Pedidos",
    "email": "$TEST_EMAIL",
    "phone": "11987654321",
    "cpf": "12345678901",
    "password": "$TEST_PASSWORD"
  },
  "delivery": {
    "is_delivery": true,
    "address": "Rua Teste",
    "number": "100",
    "neighborhood": "Centro",
    "city": "São Paulo",
    "state": "SP",
    "zip_code": "01234-567"
  },
  "products": [
    {
      "uuid": "$PRODUCT_UUID",
      "quantity": 2
    }
  ],
  "payment_method": "pix",
  "shipping_method": "delivery"
}
EOF
)

ORDER_RESPONSE=$(curl -s -X POST "${API_URL}/orders" \
  -H "Content-Type: application/json" \
  -d "$ORDER_DATA")

ORDER_SUCCESS=$(echo "$ORDER_RESPONSE" | jq -r '.success')
ORDER_ID=$(echo "$ORDER_RESPONSE" | jq -r '.data.order_id // empty')

if [ "$ORDER_SUCCESS" = "true" ]; then
    echo -e "   ${GREEN}✓ Pedido criado: #$ORDER_ID${NC}"
else
    echo -e "   ${YELLOW}⚠ Aviso: $(echo "$ORDER_RESPONSE" | jq -r '.message')${NC}"
    echo "   Continuando com pedidos existentes..."
fi

echo ""
echo -e "${YELLOW}3. Consultando pedidos do cliente...${NC}"

ORDERS_RESPONSE=$(curl -s -X GET "${API_URL}/orders" \
  -H "Authorization: Bearer $TOKEN" \
  -H "Accept: application/json")

ORDERS_SUCCESS=$(echo "$ORDERS_RESPONSE" | jq -r '.success')

if [ "$ORDERS_SUCCESS" = "true" ]; then
    TOTAL_ORDERS=$(echo "$ORDERS_RESPONSE" | jq -r '.data.total_orders')
    
    echo -e "   ${GREEN}✓ Pedidos consultados com sucesso${NC}"
    echo "   Total de pedidos: $TOTAL_ORDERS"
    
    if [ "$TOTAL_ORDERS" -gt 0 ]; then
        echo ""
        echo "   Últimos pedidos:"
        echo "$ORDERS_RESPONSE" | jq -r '.data.orders[] | "   - #\(.identify) | \(.status) | \(.formatted_total) | \(.created_at)"' | head -5
        
        echo ""
        echo -e "${YELLOW}4. Detalhes do primeiro pedido:${NC}"
        FIRST_ORDER=$(echo "$ORDERS_RESPONSE" | jq -r '.data.orders[0]')
        
        echo "   ID: $(echo "$FIRST_ORDER" | jq -r '.identify')"
        echo "   Status: $(echo "$FIRST_ORDER" | jq -r '.status')"
        echo "   Total: $(echo "$FIRST_ORDER" | jq -r '.formatted_total')"
        echo "   Data: $(echo "$FIRST_ORDER" | jq -r '.created_at')"
        echo "   Origem: $(echo "$FIRST_ORDER" | jq -r '.origin')"
        
        PRODUCTS_COUNT=$(echo "$FIRST_ORDER" | jq -r '.products | length')
        echo "   Produtos: $PRODUCTS_COUNT item(s)"
        
        echo "$FIRST_ORDER" | jq -r '.products[] | "     - \(.quantity)x \(.name) - R$ \(.subtotal)"'
        
        if [ "$(echo "$FIRST_ORDER" | jq -r '.is_delivery')" = "true" ]; then
            echo "   Entrega: $(echo "$FIRST_ORDER" | jq -r '.delivery_address'), $(echo "$FIRST_ORDER" | jq -r '.delivery_city')/$(echo "$FIRST_ORDER" | jq -r '.delivery_state')"
        fi
    else
        echo -e "   ${YELLOW}⚠ Cliente não possui pedidos${NC}"
    fi
else
    ERROR_MSG=$(echo "$ORDERS_RESPONSE" | jq -r '.message')
    echo -e "   ${RED}✗ Erro ao consultar pedidos: $ERROR_MSG${NC}"
    echo "$ORDERS_RESPONSE" | jq '.'
fi

echo ""
echo -e "${YELLOW}5. Testando sem autenticação (deve falhar)...${NC}"

NO_AUTH_RESPONSE=$(curl -s -X GET "${API_URL}/orders" \
  -H "Accept: application/json")

NO_AUTH_SUCCESS=$(echo "$NO_AUTH_RESPONSE" | jq -r '.success // "false"')

if [ "$NO_AUTH_SUCCESS" = "false" ]; then
    echo -e "   ${GREEN}✓ Acesso corretamente bloqueado sem token${NC}"
else
    echo -e "   ${RED}✗ Endpoint desprotegido!${NC}"
fi

echo ""
echo "=========================================="
echo -e "${GREEN}✅ TESTE CONCLUÍDO${NC}"
echo "=========================================="
