#!/bin/bash
# Testes para o pipeline de deploy (backend PHPUnit + frontend Jest + smoke HTTP)
# Uso: source scripts/deploy-tests.sh && run_backend_tests /path/to/backend_moday

set -e

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

log_step() { echo -e "${BLUE}▶ $1${NC}"; }
log_ok() { echo -e "${GREEN}✅ $1${NC}"; }
log_warn() { echo -e "${YELLOW}⚠️  $1${NC}"; }
log_fail() { echo -e "${RED}❌ $1${NC}"; }

# Restaura ambiente de produção após testes com composer --dev (evita Sail no cache montado)
repair_production_runtime() {
  local dir="${1:-.}"
  if [ ! -f "$dir/scripts/fix-production-runtime.sh" ]; then
    log_warn "fix-production-runtime.sh não encontrado; pulando reparo"
    return 0
  fi
  if ! docker ps --format '{{.Names}}' 2>/dev/null | grep -q '^moday-backend$'; then
    return 0
  fi
  log_step "Reparando runtime de produção (cache + storage)..."
  (cd "$dir" && bash scripts/fix-production-runtime.sh)
  log_ok "Runtime de produção reparado"
}

# Backend — PHPUnit via container PHP 8.3 + Composer (sqlite in-memory no phpunit.xml)
run_backend_tests() {
  local dir="${1:-.}"
  cd "$dir"

  if [ "${SKIP_BACKEND_TESTS:-0}" = "1" ]; then
    log_warn "Testes de backend ignorados (SKIP_BACKEND_TESTS=1)"
    return 0
  fi

  log_step "Testes backend (PHPUnit / artisan test)..."

  if [ ! -f artisan ]; then
    log_fail "Diretório Laravel inválido: $dir"
    return 1
  fi

  docker run --rm \
    -v "$(pwd):/var/www/html" \
    -w /var/www/html \
    laravelsail/php83-composer:latest \
    bash -lc '
      set -e
      composer install --no-interaction --prefer-dist --ignore-platform-reqs
      if [ -n "${DEPLOY_TEST_FILTER:-}" ]; then
        php artisan test --filter="${DEPLOY_TEST_FILTER}" --env=testing
      else
        php artisan test --env=testing
      fi
    '

  repair_production_runtime "$dir"

  log_ok "Backend: todos os testes passaram"
}

# Frontend — Jest (CI, sem watch)
run_frontend_tests() {
  local dir="${1:-.}"
  cd "$dir"

  if [ "${SKIP_FRONTEND_TESTS:-0}" = "1" ]; then
    log_warn "Testes de frontend ignorados (SKIP_FRONTEND_TESTS=1)"
    return 0
  fi

  log_step "Testes frontend (Jest)..."

  if [ ! -f package.json ]; then
    log_fail "Diretório Next.js inválido: $dir"
    return 1
  fi

  rm -f pnpm-lock.yaml 2>/dev/null || true

  if [ ! -d node_modules/jest ]; then
    npm ci --include=dev 2>/dev/null || npm install --include=dev
  fi

  export CI=true
  if [ "${FRONTEND_TEST_FULL:-0}" = "1" ]; then
    log_warn "Jest suíte completa (FRONTEND_TEST_FULL=1)..."
    npm test -- --ci --watchAll=false --passWithNoTests
  else
    log_step "Jest suíte de deploy (testes estáveis)..."
    npm run test:deploy
  fi

  log_ok "Frontend: testes OK"
}

# Smoke HTTP após deploy (executar no servidor ou com URL pública)
run_smoke_tests() {
  local base="${1:-http://127.0.0.1}"
  local failed=0

  if [ "${SKIP_SMOKE_TESTS:-0}" = "1" ]; then
    log_warn "Smoke tests ignorados (SKIP_SMOKE_TESTS=1)"
    return 0
  fi

  log_step "Smoke tests HTTP ($base)..."

  check_http() {
    local name="$1"
    local url="$2"
    local expected="${3:-200}"
    local code
    code=$(curl -s -o /dev/null -w "%{http_code}" "$url" || echo "000")
    if [ "$code" = "$expected" ]; then
      echo "  OK  $name → HTTP $code"
    else
      echo "  FAIL $name → HTTP $code (esperado $expected)"
      failed=1
    fi
  }

  check_http "Frontend /" "${base}/"
  check_http "Landing" "${base}/landing"
  check_http "Login" "${base}/login"
  check_http "API plans" "${base}/api/public/plans"

  if [ "$failed" -eq 1 ]; then
    log_fail "Smoke tests falharam"
    return 1
  fi

  log_ok "Smoke tests OK"
}
