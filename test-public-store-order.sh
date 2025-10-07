#!/bin/bash

echo "=========================================="
echo "TESTE DE CRIAÇÃO DE PEDIDO - LOJA PÚBLICA"
echo "=========================================="
echo ""

# Cores
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Get tenant slug
TENANT_SLUG="empresa-dev"

echo -e "${YELLOW}1. Buscando informações da loja...${NC}"
STORE_INFO=$(curl -s http://localhost:8000/api/store/${TENANT_SLUG}/info)
STORE_NAME=$(echo "$STORE_INFO" | jq -r '.data.name')
TENANT_ID=$(echo "$STORE_INFO" | jq -r '.data.id')

if [ "$STORE_NAME" != "null" ]; then
    echo -e "   ${GREEN}✓ Loja: $STORE_NAME (ID: $TENANT_ID)${NC}"
else
    echo -e "   ${RED}✗ Loja não encontrada${NC}"
    exit 1
fi
echo ""

echo -e "${YELLOW}2. Buscando produtos disponíveis...${NC}"
PRODUCTS=$(curl -s http://localhost:8000/api/store/${TENANT_SLUG}/products)
PRODUCT_UUID=$(echo "$PRODUCTS" | jq -r '.data[0].uuid')
PRODUCT_NAME=$(echo "$PRODUCTS" | jq -r '.data[0].name')
PRODUCT_PRICE=$(echo "$PRODUCTS" | jq -r '.data[0].price')

if [ "$PRODUCT_UUID" != "null" ]; then
    echo -e "   ${GREEN}✓ Produto: $PRODUCT_NAME (R$ $PRODUCT_PRICE)${NC}"
else
    echo -e "   ${RED}✗ Nenhum produto disponível${NC}"
    exit 1
fi
echo ""

echo -e "${YELLOW}3. Criando pedido na loja pública...${NC}"

ORDER_DATA=$(cat <<EOF
{
  "client": {
    "name": "João Silva Teste",
    "email": "joao.teste@exemplo.com",
    "phone": "11987654321",
    "cpf": null
  },
  "delivery": {
    "is_delivery": true,
    "address": "Rua das Flores",
    "number": "123",
    "neighborhood": "Centro",
    "city": "São Paulo",
    "state": "SP",
    "zip_code": "01234-567",
    "complement": "Apto 45",
    "notes": "Portão azul"
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

ORDER_RESPONSE=$(curl -s -X POST "http://localhost:8000/api/store/${TENANT_SLUG}/orders" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d "$ORDER_DATA")

# Check response
SUCCESS=$(echo "$ORDER_RESPONSE" | jq -r '.success')

if [ "$SUCCESS" = "true" ]; then
    ORDER_ID=$(echo "$ORDER_RESPONSE" | jq -r '.data.order_id')
    TOTAL=$(echo "$ORDER_RESPONSE" | jq -r '.data.total')
    
    echo -e "   ${GREEN}✓ Pedido criado com sucesso!${NC}"
    echo "   - ID: $ORDER_ID"
    echo "   - Total: R$ $TOTAL"
    echo ""
    
    # Verificar origem do pedido no banco
    echo -e "${YELLOW}4. Verificando origem do pedido no banco...${NC}"
    
    ORDER_CHECK=$(cd /Users/fabiosantana/Documentos/projetos/moday/backend && php artisan tinker --execute="
        \$order = App\Models\Order::where('identify', '$ORDER_ID')->first();
        if (\$order) {
            echo json_encode([
                'identify' => \$order->identify,
                'origin' => \$order->origin,
                'status' => \$order->status,
                'total' => \$order->total,
                'client_name' => \$order->client->name ?? 'N/A'
            ]);
        }
    ")
    
    ORIGIN=$(echo "$ORDER_CHECK" | jq -r '.origin')
    CLIENT_NAME=$(echo "$ORDER_CHECK" | jq -r '.client_name')
    
    if [ "$ORIGIN" = "public_store" ]; then
        echo -e "   ${GREEN}✓ Origin: $ORIGIN${NC}"
        echo "   - Cliente: $CLIENT_NAME"
        echo "   - Status: $(echo "$ORDER_CHECK" | jq -r '.status')"
    else
        echo -e "   ${RED}✗ Origin incorreta: $ORIGIN (esperado: public_store)${NC}"
    fi
    
    echo ""
    echo -e "${YELLOW}5. Verificando cliente criado...${NC}"
    
    CLIENT_CHECK=$(cd /Users/fabiosantana/Documentos/projetos/moday/backend && php artisan tinker --execute="
        \$client = App\Models\Client::where('email', 'joao.teste@exemplo.com')->first();
        if (\$client) {
            echo json_encode([
                'id' => \$client->id,
                'name' => \$client->name,
                'email' => \$client->email,
                'has_password' => !empty(\$client->password),
                'cpf' => \$client->cpf ?? 'NULL'
            ]);
        }
    ")
    
    HAS_PASSWORD=$(echo "$CLIENT_CHECK" | jq -r '.has_password')
    CLIENT_CPF=$(echo "$CLIENT_CHECK" | jq -r '.cpf')
    
    if [ "$HAS_PASSWORD" = "false" ]; then
        echo -e "   ${GREEN}✓ Cliente criado sem senha (correto)${NC}"
        echo "   - Email: $(echo "$CLIENT_CHECK" | jq -r '.email')"
        echo "   - CPF: $CLIENT_CPF"
    else
        echo -e "   ${YELLOW}⚠ Cliente tem senha${NC}"
    fi
    
else
    ERROR_MSG=$(echo "$ORDER_RESPONSE" | jq -r '.message')
    ERROR_DETAIL=$(echo "$ORDER_RESPONSE" | jq -r '.error // empty')
    
    echo -e "   ${RED}✗ Erro ao criar pedido${NC}"
    echo "   - Mensagem: $ERROR_MSG"
    if [ ! -z "$ERROR_DETAIL" ]; then
        echo "   - Detalhes: $ERROR_DETAIL"
    fi
    
    # Exibir resposta completa para debug
    echo ""
    echo "Resposta completa:"
    echo "$ORDER_RESPONSE" | jq '.'
fi

echo ""
echo "=========================================="
echo -e "${GREEN}TESTE CONCLUÍDO${NC}"
echo "=========================================="
