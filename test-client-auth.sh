#!/bin/bash

echo "=========================================="
echo "TESTE DE AUTENTICAÇÃO DE CLIENTES"
echo "=========================================="
echo ""

# Cores
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

TENANT_SLUG="empresa-dev"
API_URL="http://localhost:8000/api/store/${TENANT_SLUG}"

# Dados de teste
TEST_NAME="Cliente Teste Auth"
TEST_EMAIL="cliente.auth.$(date +%s)@teste.com"
TEST_PASSWORD="senha123"
TEST_PHONE="11987654321"

echo -e "${YELLOW}1. Registrando novo cliente...${NC}"

REGISTER_DATA=$(cat <<EOF
{
  "name": "$TEST_NAME",
  "email": "$TEST_EMAIL",
  "password": "$TEST_PASSWORD",
  "password_confirmation": "$TEST_PASSWORD",
  "phone": "$TEST_PHONE",
  "cpf": "12345678901"
}
EOF
)

REGISTER_RESPONSE=$(curl -s -X POST "${API_URL}/auth/register" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d "$REGISTER_DATA")

REGISTER_SUCCESS=$(echo "$REGISTER_RESPONSE" | jq -r '.success')

if [ "$REGISTER_SUCCESS" = "true" ]; then
    REGISTER_TOKEN=$(echo "$REGISTER_RESPONSE" | jq -r '.data.token')
    CLIENT_UUID=$(echo "$REGISTER_RESPONSE" | jq -r '.data.client.uuid')
    CLIENT_NAME=$(echo "$REGISTER_RESPONSE" | jq -r '.data.client.name')
    
    echo -e "   ${GREEN}✓ Cliente registrado com sucesso${NC}"
    echo "   - Nome: $CLIENT_NAME"
    echo "   - UUID: $CLIENT_UUID"
    echo "   - Token recebido: ${REGISTER_TOKEN:0:30}..."
else
    ERROR_MSG=$(echo "$REGISTER_RESPONSE" | jq -r '.message')
    echo -e "   ${RED}✗ Erro ao registrar: $ERROR_MSG${NC}"
    echo "$REGISTER_RESPONSE" | jq '.'
    exit 1
fi

echo ""
echo -e "${YELLOW}2. Testando login com credenciais...${NC}"

LOGIN_DATA=$(cat <<EOF
{
  "email": "$TEST_EMAIL",
  "password": "$TEST_PASSWORD"
}
EOF
)

LOGIN_RESPONSE=$(curl -s -X POST "${API_URL}/auth/login" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d "$LOGIN_DATA")

LOGIN_SUCCESS=$(echo "$LOGIN_RESPONSE" | jq -r '.success')

if [ "$LOGIN_SUCCESS" = "true" ]; then
    LOGIN_TOKEN=$(echo "$LOGIN_RESPONSE" | jq -r '.data.token')
    
    echo -e "   ${GREEN}✓ Login realizado com sucesso${NC}"
    echo "   - Token recebido: ${LOGIN_TOKEN:0:30}..."
else
    ERROR_MSG=$(echo "$LOGIN_RESPONSE" | jq -r '.message')
    echo -e "   ${RED}✗ Erro ao fazer login: $ERROR_MSG${NC}"
    exit 1
fi

echo ""
echo -e "${YELLOW}3. Buscando dados do cliente autenticado...${NC}"

ME_RESPONSE=$(curl -s -X GET "${API_URL}/auth/me" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $LOGIN_TOKEN")

ME_SUCCESS=$(echo "$ME_RESPONSE" | jq -r '.success')

if [ "$ME_SUCCESS" = "true" ]; then
    ME_NAME=$(echo "$ME_RESPONSE" | jq -r '.data.name')
    ME_EMAIL=$(echo "$ME_RESPONSE" | jq -r '.data.email')
    
    echo -e "   ${GREEN}✓ Dados do cliente obtidos${NC}"
    echo "   - Nome: $ME_NAME"
    echo "   - Email: $ME_EMAIL"
else
    ERROR_MSG=$(echo "$ME_RESPONSE" | jq -r '.message')
    echo -e "   ${RED}✗ Erro ao buscar dados: $ERROR_MSG${NC}"
fi

echo ""
echo -e "${YELLOW}4. Testando criação de pedido COM senha...${NC}"

# Buscar produto disponível
PRODUCT_UUID=$(curl -s "${API_URL}/products" | jq -r '.data[0].uuid')

ORDER_WITH_PASSWORD=$(cat <<EOF
{
  "client": {
    "name": "Cliente Com Senha",
    "email": "cliente.senha.$(date +%s)@teste.com",
    "phone": "11999999999",
    "cpf": null,
    "password": "minhasenha123"
  },
  "delivery": {
    "is_delivery": true,
    "address": "Rua Teste",
    "number": "100",
    "neighborhood": "Centro",
    "city": "São Paulo",
    "state": "SP",
    "zip_code": "01234-567",
    "complement": "",
    "notes": ""
  },
  "products": [
    {
      "uuid": "$PRODUCT_UUID",
      "quantity": 1
    }
  ],
  "payment_method": "pix",
  "shipping_method": "delivery"
}
EOF
)

ORDER_RESPONSE=$(curl -s -X POST "${API_URL}/orders" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d "$ORDER_WITH_PASSWORD")

ORDER_SUCCESS=$(echo "$ORDER_RESPONSE" | jq -r '.success')

if [ "$ORDER_SUCCESS" = "true" ]; then
    ORDER_ID=$(echo "$ORDER_RESPONSE" | jq -r '.data.order_id')
    echo -e "   ${GREEN}✓ Pedido criado com senha${NC}"
    echo "   - ID: $ORDER_ID"
    
    # Verificar se cliente foi criado com senha
    CLIENT_EMAIL=$(echo "$ORDER_WITH_PASSWORD" | jq -r '.client.email')
    
    CHECK_CLIENT=$(cd /Users/fabiosantana/Documentos/projetos/moday/backend && php artisan tinker --execute="
        \$client = App\Models\Client::where('email', '$CLIENT_EMAIL')->first();
        if (\$client) {
            echo json_encode([
                'has_password' => !empty(\$client->password),
                'email' => \$client->email
            ]);
        }
    ")
    
    HAS_PASSWORD=$(echo "$CHECK_CLIENT" | jq -r '.has_password')
    
    if [ "$HAS_PASSWORD" = "true" ]; then
        echo -e "   ${GREEN}✓ Cliente criado COM senha (pode fazer login)${NC}"
    else
        echo -e "   ${RED}✗ Cliente criado SEM senha${NC}"
    fi
else
    ERROR_MSG=$(echo "$ORDER_RESPONSE" | jq -r '.message')
    echo -e "   ${YELLOW}⚠ Erro ao criar pedido: $ERROR_MSG${NC}"
    echo "   (Pode ser falta de estoque)"
fi

echo ""
echo -e "${YELLOW}5. Testando logout...${NC}"

LOGOUT_RESPONSE=$(curl -s -X POST "${API_URL}/auth/logout" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -H "Authorization: Bearer $LOGIN_TOKEN")

LOGOUT_SUCCESS=$(echo "$LOGOUT_RESPONSE" | jq -r '.success')

if [ "$LOGOUT_SUCCESS" = "true" ]; then
    echo -e "   ${GREEN}✓ Logout realizado com sucesso${NC}"
else
    ERROR_MSG=$(echo "$LOGOUT_RESPONSE" | jq -r '.message')
    echo -e "   ${RED}✗ Erro ao fazer logout: $ERROR_MSG${NC}"
fi

echo ""
echo -e "${YELLOW}6. Testando login com senha incorreta...${NC}"

WRONG_LOGIN=$(cat <<EOF
{
  "email": "$TEST_EMAIL",
  "password": "senhaerrada"
}
EOF
)

WRONG_RESPONSE=$(curl -s -X POST "${API_URL}/auth/login" \
  -H "Content-Type: application/json" \
  -H "Accept: application/json" \
  -d "$WRONG_LOGIN")

WRONG_SUCCESS=$(echo "$WRONG_RESPONSE" | jq -r '.success')

if [ "$WRONG_SUCCESS" = "false" ]; then
    echo -e "   ${GREEN}✓ Login corretamente rejeitado (senha incorreta)${NC}"
else
    echo -e "   ${RED}✗ Login aceito com senha incorreta!${NC}"
fi

echo ""
echo "=========================================="
echo -e "${GREEN}✅ TESTES CONCLUÍDOS${NC}"
echo "=========================================="
