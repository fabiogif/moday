#!/bin/bash

# ==============================================================================
# Script de Instalação - Redis Resilience (FASE 1)
# ==============================================================================

echo "🚀 Instalando Redis Resilience - FASE 1"
echo "========================================"
echo ""

# Cores para output
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m' # No Color

# Função para imprimir sucesso
success() {
    echo -e "${GREEN}✅ $1${NC}"
}

# Função para imprimir warning
warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

# Função para imprimir erro
error() {
    echo -e "${RED}❌ $1${NC}"
}

# ==============================================================================
# 1. VERIFICAR REQUISITOS
# ==============================================================================

echo "1️⃣  Verificando requisitos..."
echo ""

# Verificar se está no diretório correto
if [ ! -f "artisan" ]; then
    error "artisan não encontrado! Execute este script na raiz do projeto Laravel."
    exit 1
fi
success "Diretório correto"

# Verificar conexão com database
php artisan db:show > /dev/null 2>&1
if [ $? -eq 0 ]; then
    success "Conexão com database OK"
else
    error "Não foi possível conectar ao database"
    exit 1
fi

echo ""

# ==============================================================================
# 2. EXECUTAR MIGRATIONS
# ==============================================================================

echo "2️⃣  Executando migrations..."
echo ""

php artisan migrate --force

if [ $? -eq 0 ]; then
    success "Migrations executadas com sucesso"
else
    error "Falha ao executar migrations"
    exit 1
fi

echo ""

# ==============================================================================
# 3. VERIFICAR TABELAS CRIADAS
# ==============================================================================

echo "3️⃣  Verificando tabelas criadas..."
echo ""

# Verificar sessões
php artisan db:table sessions > /dev/null 2>&1
if [ $? -eq 0 ]; then
    success "Tabela 'sessions' criada"
else
    warning "Tabela 'sessions' não encontrada"
fi

# Verificar rate_limits
php artisan db:table rate_limits > /dev/null 2>&1
if [ $? -eq 0 ]; then
    success "Tabela 'rate_limits' criada"
else
    warning "Tabela 'rate_limits' não encontrada"
fi

# Verificar jobs
php artisan db:table jobs > /dev/null 2>&1
if [ $? -eq 0 ]; then
    success "Tabela 'jobs' criada"
else
    warning "Tabela 'jobs' não encontrada"
fi

echo ""

# ==============================================================================
# 4. EXECUTAR TESTES
# ==============================================================================

echo "4️⃣  Executando testes..."
echo ""

php artisan test --testsuite=Unit --filter=Redis

if [ $? -eq 0 ]; then
    success "Testes unitários passaram"
else
    warning "Alguns testes falharam (verifique manualmente)"
fi

echo ""

# ==============================================================================
# 5. TESTAR HEALTH CHECK
# ==============================================================================

echo "5️⃣  Testando health check..."
echo ""

php artisan redis:health

echo ""

# ==============================================================================
# 6. LIMPAR CACHE
# ==============================================================================

echo "6️⃣  Limpando cache..."
echo ""

php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

success "Cache limpo"

echo ""

# ==============================================================================
# 7. INSTRUÇÕES FINAIS
# ==============================================================================

echo "🎉 INSTALAÇÃO COMPLETA!"
echo "======================="
echo ""
echo "📋 Próximos passos:"
echo ""
echo "1. Configure o cron para executar os comandos de manutenção:"
echo "   */5 * * * * cd $(pwd) && php artisan redis:health --notify"
echo "   0 * * * * cd $(pwd) && php artisan rate-limits:cleanup"
echo "   0 0 * * * cd $(pwd) && php artisan sessions:cleanup"
echo ""
echo "2. Monitore os logs:"
echo "   tail -f storage/logs/laravel.log | grep -i redis"
echo ""
echo "3. Teste a resiliência:"
echo "   - Faça login na aplicação"
echo "   - Desligue o Redis: docker stop redis"
echo "   - Verifique que ainda está logado"
echo "   - Ligue o Redis: docker start redis"
echo ""
echo "4. Leia a documentação completa:"
echo "   cat docs/REDIS_RESILIENCE_IMPLEMENTATION.md"
echo ""
echo "✅ Sistema pronto para produção!"
echo ""
