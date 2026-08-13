#!/bin/bash

# ==============================================================================
# Script de Monitoramento de Cache
# ==============================================================================

echo "📊 Monitoramento de Performance do Cache"
echo "=========================================="
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
# 1. VERIFICAR REDIS
# ==============================================================================

echo "1️⃣  Verificando Redis..."
echo ""

if ! docker ps | grep -q redis; then
    warning "Redis não está rodando via Docker"
    
    # Tentar conectar diretamente
    if redis-cli ping > /dev/null 2>&1; then
        success "Redis rodando localmente"
    else
        error "Redis não disponível"
        echo "Inicie o Redis: docker-compose up -d redis"
        exit 1
    fi
else
    success "Redis rodando no Docker"
fi

echo ""

# ==============================================================================
# 2. ESTATÍSTICAS DO REDIS
# ==============================================================================

echo "2️⃣  Estatísticas do Redis..."
echo ""

# Conectar ao Redis (tentar Docker primeiro, depois local)
if docker ps | grep -q redis; then
    REDIS_CLI="docker exec backend_moday-redis-1 redis-cli"
else
    REDIS_CLI="redis-cli"
fi

# Total de chaves
TOTAL_KEYS=$($REDIS_CLI DBSIZE | awk '{print $2}')
echo "📦 Total de chaves: $TOTAL_KEYS"

# Memória usada
MEMORY_USED=$($REDIS_CLI INFO memory | grep "used_memory_human" | cut -d':' -f2 | tr -d '\r')
echo "💾 Memória usada: $MEMORY_USED"

# Hit rate
KEYSPACE_HITS=$($REDIS_CLI INFO stats | grep "keyspace_hits" | cut -d':' -f2 | tr -d '\r')
KEYSPACE_MISSES=$($REDIS_CLI INFO stats | grep "keyspace_misses" | cut -d':' -f2 | tr -d '\r')

if [ "$KEYSPACE_HITS" -gt 0 ] || [ "$KEYSPACE_MISSES" -gt 0 ]; then
    TOTAL_REQUESTS=$((KEYSPACE_HITS + KEYSPACE_MISSES))
    HIT_RATE=$(awk "BEGIN {printf \"%.2f\", ($KEYSPACE_HITS / $TOTAL_REQUESTS) * 100}")
    
    echo "🎯 Cache hits: $KEYSPACE_HITS"
    echo "❌ Cache misses: $KEYSPACE_MISSES"
    echo "📈 Hit rate: ${HIT_RATE}%"
    
    if (( $(echo "$HIT_RATE > 80" | bc -l) )); then
        success "Excelente hit rate! (>80%)"
    elif (( $(echo "$HIT_RATE > 60" | bc -l) )); then
        info "Hit rate bom (60-80%)"
    else
        warning "Hit rate baixo (<60%) - considere aumentar TTL"
    fi
else
    info "Nenhum dado de hit/miss ainda"
fi

echo ""

# ==============================================================================
# 3. CHAVES MAIS USADAS
# ==============================================================================

echo "3️⃣  Chaves de Cache por Padrão..."
echo ""

# Contar chaves por padrão
echo "Produtos:"
PRODUCT_KEYS=$($REDIS_CLI KEYS "laravel_cache:product*" | wc -l)
echo "  └─ $PRODUCT_KEYS chaves"

echo "Categorias:"
CATEGORY_KEYS=$($REDIS_CLI KEYS "laravel_cache:categor*" | wc -l)
echo "  └─ $CATEGORY_KEYS chaves"

echo "Pedidos:"
ORDER_KEYS=$($REDIS_CLI KEYS "laravel_cache:order*" | wc -l)
echo "  └─ $ORDER_KEYS chaves"

echo "Clientes:"
CLIENT_KEYS=$($REDIS_CLI KEYS "laravel_cache:client*" | wc -l)
echo "  └─ $CLIENT_KEYS chaves"

echo "Dashboard:"
DASHBOARD_KEYS=$($REDIS_CLI KEYS "laravel_cache:dashboard*" | wc -l)
echo "  └─ $DASHBOARD_KEYS chaves"

echo "Planos:"
PLAN_KEYS=$($REDIS_CLI KEYS "laravel_cache:plan*" | wc -l)
echo "  └─ $PLAN_KEYS chaves"

echo ""

# ==============================================================================
# 4. TAMANHO DAS CHAVES
# ==============================================================================

echo "4️⃣  Top 10 Maiores Chaves..."
echo ""

$REDIS_CLI --scan | head -20 | while read key; do
    SIZE=$($REDIS_CLI MEMORY USAGE "$key" 2>/dev/null || echo "0")
    if [ "$SIZE" != "0" ]; then
        echo "  • $(basename $key): ${SIZE} bytes"
    fi
done | sort -rn -k2 | head -10

echo ""

# ==============================================================================
# 5. TTL DAS CHAVES
# ==============================================================================

echo "5️⃣  Verificando TTL das Chaves..."
echo ""

# Amostra de chaves com TTL
SAMPLE_KEYS=$($REDIS_CLI KEYS "laravel_cache:*" | head -5)

if [ -z "$SAMPLE_KEYS" ]; then
    info "Nenhuma chave de cache encontrada"
else
    echo "$SAMPLE_KEYS" | while read key; do
        TTL=$($REDIS_CLI TTL "$key")
        KEY_NAME=$(basename "$key")
        
        if [ "$TTL" -eq -1 ]; then
            warning "$KEY_NAME: SEM EXPIRAÇÃO (TTL infinito)"
        elif [ "$TTL" -eq -2 ]; then
            info "$KEY_NAME: Expirada"
        else
            TTL_MIN=$((TTL / 60))
            success "$KEY_NAME: ${TTL_MIN} minutos restantes"
        fi
    done
fi

echo ""

# ==============================================================================
# 6. COMANDOS ÚTEIS
# ==============================================================================

echo "🔧 Comandos Úteis:"
echo "=================="
echo ""
echo "# Ver todas as chaves de um tipo:"
echo "  redis-cli KEYS 'laravel_cache:product*'"
echo ""
echo "# Limpar cache de produtos:"
echo "  redis-cli DEL \$(redis-cli KEYS 'laravel_cache:product*')"
echo ""
echo "# Monitorar comandos em tempo real:"
echo "  redis-cli MONITOR"
echo ""
echo "# Ver info completa:"
echo "  redis-cli INFO"
echo ""
echo "# Limpar TODO o cache (CUIDADO!):"
echo "  redis-cli FLUSHDB"
echo ""
echo "# Ver hit rate ao vivo:"
echo "  watch -n 1 'redis-cli INFO stats | grep keyspace'"
echo ""

# ==============================================================================
# 7. RECOMENDAÇÕES
# ==============================================================================

echo "💡 Recomendações:"
echo "================="
echo ""

# Verificar hit rate
if [ ! -z "$HIT_RATE" ]; then
    if (( $(echo "$HIT_RATE < 70" | bc -l) )); then
        warning "Hit rate abaixo de 70% - Considere:"
        echo "  1. Aumentar TTL das chaves mais usadas"
        echo "  2. Verificar se invalidação não é muito frequente"
        echo "  3. Adicionar cache em mais endpoints"
    fi
fi

# Verificar número de chaves
if [ "$TOTAL_KEYS" -gt 10000 ]; then
    warning "Muitas chaves em cache (>10k)"
    echo "  1. Verificar se há memory leaks"
    echo "  2. Implementar cleanup de chaves antigas"
    echo "  3. Revisar TTL das chaves"
fi

# Verificar memória
MEMORY_MB=$(echo "$MEMORY_USED" | sed 's/M//' | sed 's/K//')
if [ ! -z "$MEMORY_MB" ]; then
    if (( $(echo "$MEMORY_MB > 500" | bc -l 2>/dev/null) )); then
        warning "Uso de memória alto (>500MB)"
        echo "  1. Verificar tamanho das chaves"
        echo "  2. Implementar max memory policy"
        echo "  3. Considerar Redis clustering"
    fi
fi

echo ""
echo "✅ Monitoramento completo!"
echo ""
