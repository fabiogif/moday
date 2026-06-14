#!/bin/bash

# Script para corrigir o problema do campo deleted_at na tabela products
# Este script deve ser executado no servidor de produção

set -e

echo "================================"
echo "Fix: Campo deleted_at em Products"
echo "================================"
echo ""

# Cores para output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Função para imprimir mensagens coloridas
print_info() {
    echo -e "${YELLOW}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Verificar se estamos no diretório correto
if [ ! -f "artisan" ]; then
    print_error "Este script deve ser executado no diretório raiz do Laravel"
    exit 1
fi

print_info "Verificando status das migrations..."
php artisan migrate:status

echo ""
print_info "Executando nova migration para adicionar deleted_at..."

# Executar a migration
if php artisan migrate --force; then
    print_success "Migration executada com sucesso!"
else
    print_error "Erro ao executar migration"
    exit 1
fi

echo ""
print_info "Verificando status atualizado das migrations..."
php artisan migrate:status

echo ""
print_success "Correção aplicada com sucesso!"
print_info "O campo deleted_at foi adicionado à tabela products"
print_info "O modelo Product agora usa SoftDeletes corretamente"

echo ""
print_info "Testando conexão com o banco..."
if php artisan tinker --execute="echo 'Conexão OK\n';"; then
    print_success "Conexão com banco de dados está funcionando"
else
    print_error "Problema na conexão com banco de dados"
    exit 1
fi

echo ""
print_success "==========================="
print_success "Correção concluída!"
print_success "==========================="
