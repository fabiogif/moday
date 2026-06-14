#!/bin/bash

# ==============================================================================
# Script de Teste Completo - Redis Resilience
# ==============================================================================

echo "🧪 Executando Suite Completa de Testes - Redis Resilience"
echo "=========================================================="
echo ""

# Cores
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
BLUE='\033[0;34m'
NC='\033[0m'

success() { echo -e "${GREEN}✅ $1${NC}"; }
warning() { echo -e "${YELLOW}⚠️  $1${NC}"; }
error() { echo -e "${RED}❌ $1${NC}"; }
info() { echo -e "${BLUE}ℹ️  $1${NC}"; }

# ==============================================================================
# 1. PREPARAÇÃO
# ==============================================================================

echo "1️⃣  Preparando ambiente de teste..."
echo ""

# Verificar se está no diretório correto
if [ ! -f "artisan" ]; then
    error "Execute este script na raiz do projeto Laravel"
    exit 1
fi
success "Diretório correto"

# Limpar cache
php artisan config:clear > /dev/null 2>&1
php artisan cache:clear > /dev/null 2>&1
success "Cache limpo"

echo ""

# ==============================================================================
# 2. EXECUTAR MIGRATIONS DE TESTE
# ==============================================================================

echo "2️⃣  Executando migrations..."
echo ""

# Migrations no ambiente de teste
php artisan migrate --env=testing --force > /dev/null 2>&1

if [ $? -eq 0 ]; then
    success "Migrations executadas"
else
    error "Falha nas migrations"
    exit 1
fi

echo ""

# ==============================================================================
# 3. TESTES UNITÁRIOS
# ==============================================================================

echo "3️⃣  Executando Testes Unitários..."
echo ""

info "→ RedisHelperTest"
php artisan test --filter=RedisHelperTest --compact

info "→ HybridSessionHandlerTest"
php artisan test --filter=HybridSessionHandlerTest --compact

info "→ HybridRateLimiterTest"
php artisan test --filter=HybridRateLimiterTest --compact

echo ""

# ==============================================================================
# 4. TESTES DE INTEGRAÇÃO
# ==============================================================================

echo "4️⃣  Executando Testes de Integração..."
echo ""

info "→ RedisResilienceTest"
php artisan test --filter=RedisResilienceTest --compact

info "→ SessionFallbackTest"
php artisan test --filter=SessionFallbackTest --compact

info "→ RateLimitFallbackTest"
php artisan test --filter=RateLimitFallbackTest --compact

info "→ QueueFallbackTest"
php artisan test --filter=QueueFallbackTest --compact

echo ""

# ==============================================================================
# 5. EXECUTAR TODOS OS TESTES
# ==============================================================================

echo "5️⃣  Executando Suite Completa..."
echo ""

php artisan test --testsuite=Unit,Feature --parallel

UNIT_EXIT_CODE=$?

echo ""

# ==============================================================================
# 6. CODE COVERAGE (OPCIONAL)
# ==============================================================================

if command -v php-coverage &> /dev/null; then
    echo "6️⃣  Gerando Code Coverage..."
    echo ""
    
    php artisan test --coverage --min=70
    
    if [ $? -eq 0 ]; then
        success "Coverage >= 70%"
    else
        warning "Coverage < 70%"
    fi
    echo ""
fi

# ==============================================================================
# 7. RELATÓRIO DE RESULTADOS
# ==============================================================================

echo "📊 RELATÓRIO DE TESTES"
echo "====================="
echo ""

if [ $UNIT_EXIT_CODE -eq 0 ]; then
    success "Todos os testes passaram!"
    echo ""
    echo "✅ Testes Unitários: OK"
    echo "✅ Testes de Integração: OK"
    echo "✅ Sistema resiliente a falhas do Redis"
    echo ""
    success "Sistema pronto para deploy!"
else
    error "Alguns testes falharam"
    echo ""
    echo "📋 Próximos passos:"
    echo "1. Verificar logs de erro acima"
    echo "2. Corrigir problemas"
    echo "3. Executar novamente"
    echo ""
    exit 1
fi

# ==============================================================================
# 8. COMANDOS ÚTEIS
# ==============================================================================

echo ""
echo "🔧 Comandos Úteis:"
echo "=================="
echo ""
echo "# Executar teste específico:"
echo "  php artisan test --filter=nome_do_teste"
echo ""
echo "# Executar com debug:"
echo "  php artisan test --verbose"
echo ""
echo "# Executar com coverage:"
echo "  php artisan test --coverage"
echo ""
echo "# Verificar Redis health:"
echo "  php artisan redis:health"
echo ""
echo "# Cleanup de sessões:"
echo "  php artisan sessions:cleanup"
echo ""
echo "# Cleanup de rate limits:"
echo "  php artisan rate-limits:cleanup"
echo ""

exit 0
