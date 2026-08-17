#!/bin/bash

# Script de Gerenciamento do Laravel Reverb
# Este script facilita o gerenciamento do servidor WebSocket Reverb

# Cores para output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Banner
echo -e "${GREEN}"
echo "╔══════════════════════════════════════╗"
echo "║   Laravel Reverb - Gerenciador       ║"
echo "║   WebSocket Server                   ║"
echo "╚══════════════════════════════════════╝"
echo -e "${NC}"

# Funções
start_reverb() {
    echo -e "${YELLOW}Iniciando Reverb...${NC}"
    docker-compose up -d reverb
    echo -e "${GREEN}✓ Reverb iniciado com sucesso!${NC}"
    echo -e "${GREEN}  Acesso: ws://localhost:8080${NC}"
}

stop_reverb() {
    echo -e "${YELLOW}Parando Reverb...${NC}"
    docker-compose stop reverb
    echo -e "${GREEN}✓ Reverb parado!${NC}"
}

restart_reverb() {
    echo -e "${YELLOW}Reiniciando Reverb...${NC}"
    docker-compose restart reverb
    echo -e "${GREEN}✓ Reverb reiniciado!${NC}"
}

status_reverb() {
    echo -e "${YELLOW}Status do Reverb:${NC}"
    docker-compose ps reverb
}

logs_reverb() {
    echo -e "${YELLOW}Logs do Reverb (Ctrl+C para sair):${NC}"
    docker-compose logs -f reverb
}

logs_tail() {
    echo -e "${YELLOW}Últimas 50 linhas dos logs:${NC}"
    docker-compose logs --tail=50 reverb
}

test_connection() {
    echo -e "${YELLOW}Testando conexão com o Reverb...${NC}"
    
    # Verifica se o container está rodando
    if docker-compose ps reverb | grep -q "Up"; then
        echo -e "${GREEN}✓ Container está rodando${NC}"
        
        # Tenta fazer uma requisição
        if curl -s -I http://localhost:8080 > /dev/null 2>&1; then
            echo -e "${GREEN}✓ Servidor está acessível na porta 8080${NC}"
        else
            echo -e "${RED}✗ Servidor não está acessível${NC}"
        fi
    else
        echo -e "${RED}✗ Container não está rodando${NC}"
    fi
}

show_config() {
    echo -e "${YELLOW}Configuração do Reverb:${NC}"
    echo ""
    grep -E "REVERB_|BROADCAST_" .env | grep -v "^#"
}

show_help() {
    echo "Uso: $0 [comando]"
    echo ""
    echo "Comandos disponíveis:"
    echo "  start       - Inicia o servidor Reverb"
    echo "  stop        - Para o servidor Reverb"
    echo "  restart     - Reinicia o servidor Reverb"
    echo "  status      - Mostra o status do container"
    echo "  logs        - Mostra logs em tempo real"
    echo "  tail        - Mostra últimas 50 linhas dos logs"
    echo "  test        - Testa a conexão com o servidor"
    echo "  config      - Mostra a configuração atual"
    echo "  help        - Mostra esta ajuda"
    echo ""
    echo "Exemplos:"
    echo "  $0 start    # Inicia o Reverb"
    echo "  $0 logs     # Acompanha os logs"
}

# Processar comando
case "${1}" in
    start)
        start_reverb
        ;;
    stop)
        stop_reverb
        ;;
    restart)
        restart_reverb
        ;;
    status)
        status_reverb
        ;;
    logs)
        logs_reverb
        ;;
    tail)
        logs_tail
        ;;
    test)
        test_connection
        ;;
    config)
        show_config
        ;;
    help|--help|-h)
        show_help
        ;;
    *)
        echo -e "${RED}Comando inválido: ${1}${NC}"
        echo ""
        show_help
        exit 1
        ;;
esac
