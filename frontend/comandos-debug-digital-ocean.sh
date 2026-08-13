#!/bin/bash
# Comandos úteis para debug do deploy na Digital Ocean

echo "=== Comandos de Debug - Digital Ocean ==="
echo ""

# Cores para output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

echo -e "${YELLOW}1. Listar todos os apps${NC}"
echo "doctl apps list"
echo ""

echo -e "${YELLOW}2. Ver detalhes de um app específico${NC}"
echo "doctl apps get SEU_APP_ID"
echo ""

echo -e "${YELLOW}3. Ver especificação atual do app${NC}"
echo "doctl apps spec get SEU_APP_ID"
echo ""

echo -e "${YELLOW}4. Ver logs de build${NC}"
echo "doctl apps logs SEU_APP_ID --type build"
echo ""

echo -e "${YELLOW}5. Ver logs de runtime${NC}"
echo "doctl apps logs SEU_APP_ID --type run"
echo ""

echo -e "${YELLOW}6. Ver logs em tempo real${NC}"
echo "doctl apps logs SEU_APP_ID --follow"
echo ""

echo -e "${YELLOW}7. Ver deployments recentes${NC}"
echo "doctl apps list-deployments SEU_APP_ID"
echo ""

echo -e "${YELLOW}8. Atualizar app com novo spec${NC}"
echo "doctl apps update SEU_APP_ID --spec .do/app.yaml"
echo ""

echo -e "${YELLOW}9. Criar novo app${NC}"
echo "doctl apps create --spec .do/app.yaml"
echo ""

echo -e "${YELLOW}10. Forçar rebuild${NC}"
echo "doctl apps create-deployment SEU_APP_ID --force-rebuild"
echo ""

echo -e "${GREEN}=== Testes Locais ===${NC}"
echo ""

echo -e "${YELLOW}11. Build Docker local${NC}"
echo "docker build -t moday-frontend ."
echo ""

echo -e "${YELLOW}12. Rodar container local${NC}"
echo "docker run -p 3000:3000 moday-frontend"
echo ""

echo -e "${YELLOW}13. Ver tamanho da imagem${NC}"
echo "docker images moday-frontend"
echo ""

echo -e "${YELLOW}14. Inspecionar container em execução${NC}"
echo "docker ps"
echo "docker exec -it CONTAINER_ID sh"
echo ""

echo -e "${GREEN}=== Verificações Importantes ===${NC}"
echo ""

echo -e "${YELLOW}15. Verificar se pnpm existe no projeto (não deveria!)${NC}"
echo "find . -name 'pnpm-lock.yaml' -o -name '.pnpmfile.cjs'"
echo ""

echo -e "${YELLOW}16. Verificar package managers${NC}"
echo "ls -la | grep -E '(package-lock|yarn.lock|pnpm-lock)'"
echo ""

echo -e "${YELLOW}17. Ver configuração do Dockerfile${NC}"
echo "cat Dockerfile"
echo ""

echo -e "${GREEN}=== Instalação doctl (se necessário) ===${NC}"
echo ""
echo "# macOS"
echo "brew install doctl"
echo ""
echo "# Linux"
echo "cd ~ && wget https://github.com/digitalocean/doctl/releases/download/v1.98.0/doctl-1.98.0-linux-amd64.tar.gz"
echo "tar xf ~/doctl-1.98.0-linux-amd64.tar.gz"
echo "sudo mv ~/doctl /usr/local/bin"
echo ""
echo "# Autenticar"
echo "doctl auth init"
echo ""

echo -e "${GREEN}=== Para pegar seu APP_ID ===${NC}"
echo "doctl apps list"
echo ""
echo -e "${RED}Substitua 'SEU_APP_ID' nos comandos acima pelo ID real do seu app!${NC}"

