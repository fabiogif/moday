#!/bin/bash

echo "📤 Enviando script de deploy para o servidor"
echo "============================================="
echo ""

# Cores
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

SERVER="orca-app-7hejo.ondigitalocean.app"
SCRIPT_NAME="deploy-on-server.sh"

echo -e "${YELLOW}🔍 Verificando se o script existe...${NC}"
if [ ! -f "$SCRIPT_NAME" ]; then
    echo -e "${RED}❌ Arquivo ${SCRIPT_NAME} não encontrado${NC}"
    exit 1
fi
echo -e "${GREEN}✅ Script encontrado${NC}"
echo ""

echo -e "${YELLOW}📤 Enviando script para o servidor...${NC}"
echo "Servidor: ${SERVER}"
echo "Destino: /root/${SCRIPT_NAME}"
echo ""

# Enviar script via SCP
scp "$SCRIPT_NAME" "root@${SERVER}:/root/"

if [ $? -eq 0 ]; then
    echo ""
    echo -e "${GREEN}✅ Script enviado com sucesso!${NC}"
    echo ""
    echo -e "${YELLOW}📋 Próximos passos:${NC}"
    echo ""
    echo "1. Conecte ao servidor:"
    echo -e "   ${BLUE}ssh root@${SERVER}${NC}"
    echo ""
    echo "2. Execute o script de deploy:"
    echo -e "   ${BLUE}cd /root${NC}"
    echo -e "   ${BLUE}chmod +x ${SCRIPT_NAME}${NC}"
    echo -e "   ${BLUE}./${SCRIPT_NAME}${NC}"
    echo ""
    echo -e "${GREEN}Ou execute tudo de uma vez:${NC}"
    echo -e "${BLUE}ssh root@${SERVER} 'cd /root && chmod +x ${SCRIPT_NAME} && ./${SCRIPT_NAME}'${NC}"
    echo ""
else
    echo ""
    echo -e "${RED}❌ Erro ao enviar script${NC}"
    echo "Verifique:"
    echo "  - Se você tem acesso SSH ao servidor"
    echo "  - Se as credenciais estão corretas"
    echo "  - Se o servidor está acessível"
    exit 1
fi
