#!/bin/bash

# Deploy no Oracle Cloud (SSH). O build do Next.js no ARM leva ~10–20 min e imprime pouco no console.

set -e

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

log_info() { echo -e "${BLUE}ℹ️  $1${NC}"; }
log_success() { echo -e "${GREEN}✅ $1${NC}"; }
log_warning() { echo -e "${YELLOW}⚠️  $1${NC}"; }
log_error() { echo -e "${RED}❌ $1${NC}"; }

ORACLE_IP="${ORACLE_IP:-163.176.233.174}"
ORACLE_USER="${ORACLE_USER:-ubuntu}"
resolve_monorepo_root() {
  local dir
  dir="$(cd "$(dirname "$0")" && pwd)"
  if [ -d "${dir}/moday_frontend" ] && [ -d "${dir}/backend_moday" ]; then
    echo "$dir"
  elif [ -d "${dir}/../moday_frontend" ] && [ -d "${dir}/../backend_moday" ]; then
    cd "${dir}/.." && pwd
  elif [ -d "${dir}/../../moday_frontend" ] && [ -d "${dir}/../../backend_moday" ]; then
    cd "${dir}/../.." && pwd
  else
    echo "$dir"
  fi
}
SCRIPT_DIR="$(resolve_monorepo_root)"
SSH_OPTS="-o ConnectTimeout=15 -o ServerAliveInterval=30 -o ServerAliveCountMax=120"

ssh_run() {
  ssh ${SSH_OPTS} "${ORACLE_USER}@${ORACLE_IP}" "$@"
}

echo "========================================="
echo "🚀 Deploy Alba Tec - Produção Oracle Cloud"
echo "========================================="
echo ""

log_info "Verificando conexão SSH..."
if ! ssh_run "echo ok" >/dev/null 2>&1; then
  log_error "Não foi possível conectar ao servidor"
  exit 1
fi
log_success "Conexão SSH OK"

# Opção não interativa: DEPLOY_OPTION=1|2|3|4|5 ./deploy-production.sh
OPTION="${DEPLOY_OPTION:-}"
if [ -z "$OPTION" ]; then
  echo ""
  echo "Escolha o que deseja fazer:"
  echo "1) Deploy completo (Backend + Frontend)"
  echo "2) Deploy apenas Backend"
  echo "3) Deploy apenas Frontend"
  echo "4) Atualizar .env de produção"
  echo "5) Ver status dos serviços"
  echo "0) Sair"
  echo ""
  read -r -p "Opção: " OPTION
fi

deploy_backend_remote() {
  ssh_run 'bash -s' << 'ENDSSH'
set -e
cd ~/apps/moday-backend

docker_build_with_retry() {
  local compose_file="$1"
  local max_attempts="${DOCKER_BUILD_RETRIES:-3}"
  local attempt=1

  while [ "$attempt" -le "$max_attempts" ]; do
    echo "🔨 Docker build (tentativa ${attempt}/${max_attempts})..."
    if docker compose -f "$compose_file" build app; then
      return 0
    fi
    if [ "$attempt" -lt "$max_attempts" ]; then
      echo "⚠️  Build falhou — aguardando 30s antes de tentar novamente..."
      sleep 30
    fi
    attempt=$((attempt + 1))
  done

  if docker image inspect moday-backend-app:latest >/dev/null 2>&1; then
    echo "⚠️  Docker Hub indisponível — usando imagem moday-backend-app:latest já existente no servidor"
    return 0
  fi

  echo "❌ Build falhou e não há imagem local de fallback"
  return 1
}

sudo chown -R ubuntu:ubuntu storage bootstrap/cache 2>/dev/null || true
chmod -R u+rwX storage bootstrap/cache 2>/dev/null || true

if [ -f backend.tar.gz ]; then
  tar -xzf backend.tar.gz
  rm -f backend.tar.gz
fi

if [ ! -f .env ]; then
  cp .env.production.example .env
fi

for kv in "CACHE_STORE=redis" "CACHE_DRIVER=redis" "QUEUE_CONNECTION=redis" "SESSION_DRIVER=redis" "REDIS_HOST=redis" "REDIS_DB=0" "REDIS_CACHE_DB=1"; do
  k="${kv%%=*}"
  grep -q "^${k}=" .env && sed -i "s|^${k}=.*|${kv}|" .env || echo "$kv" >> .env
done

for kv in "MAIL_MAILER=smtp" "MAIL_CONTACT_TO=contato@albatec.com.br" "MAIL_SUPPORT_TO=atendimento@albatec.com.br" "MAIL_PIX_TO=pix@albatec.com.br" "MAIL_PROVIDER=smtp"; do
  k="${kv%%=*}"
  grep -q "^${k}=" .env || echo "$kv" >> .env
done

mkdir -p storage/framework/{sessions,views,cache/data} storage/logs bootstrap/cache
chmod -R 775 storage bootstrap/cache 2>/dev/null || true

if [ "${SKIP_DOCKER_BUILD:-0}" = "1" ]; then
  echo "⏭️  SKIP_DOCKER_BUILD=1 — pulando rebuild da imagem"
elif ! docker_build_with_retry docker-compose.production.yml; then
  exit 1
fi

docker compose -f docker-compose.production.yml up -d --force-recreate

echo "⏳ Aguardando MySQL/Redis..."
sleep 25

rm -f bootstrap/cache/packages.php bootstrap/cache/services.php bootstrap/cache/config.php 2>/dev/null || true
bash scripts/fix-production-runtime.sh 2>/dev/null || true
docker exec moday-backend php artisan cache:clear
docker exec moday-backend php artisan package:discover --ansi
docker exec moday-backend php artisan migrate --force
docker exec moday-backend php artisan db:seed --class=DefaultOrderStatusesSeeder --force 2>/dev/null || true
docker exec moday-backend php artisan fix:media-urls 2>/dev/null || true
bash scripts/setup-instance-storage.sh 2>/dev/null || true
docker exec moday-backend php artisan config:clear
docker exec moday-backend php artisan config:cache
docker exec moday-backend php artisan route:clear
docker compose -f docker-compose.production.yml up -d app queue-worker
sleep 5
bash scripts/fix-production-runtime.sh 2>/dev/null || true

code=$(curl -s -o /dev/null -w "%{http_code}" http://127.0.0.1:8000/api/public/plans)
echo "API local :8000 → HTTP ${code}"

if docker ps --format '{{.Names}}' | grep -q moday-queue-worker; then
  echo "✅ queue-worker rodando"
else
  echo "⚠️ queue-worker ausente — subindo..."
  docker compose -f docker-compose.production.yml up -d queue-worker
fi
ENDSSH
}

deploy_frontend_remote() {
  ssh_run 'bash -s' << 'ENDSSH'
set -e
cd ~/apps/moday-frontend

# NÃO matar node server.js aqui — evita 502 no nginx durante o build (causa #1 do erro recorrente)
echo "🛑 Parando apenas builds antigos (frontend continua no ar)..."
pkill -f 'next build' 2>/dev/null || true
sleep 1

if [ -f frontend.tar.gz ]; then
  echo "📦 Extraindo frontend..."
  tar -xzf frontend.tar.gz
  rm -f frontend.tar.gz
fi

rm -f .env.local
cp .env.production.example .env.production

if [ ! -d node_modules ] || [ ! -f node_modules/next/package.json ]; then
  echo "📥 npm ci (primeira vez ou node_modules ausente)..."
  npm ci --include=dev
else
  echo "📥 npm install..."
  npm install --include=dev
fi

export NODE_ENV=production
export NEXT_TELEMETRY_DISABLED=1
export NODE_OPTIONS="${NODE_OPTIONS:---max-old-space-size=6144}"

LOG="$PWD/build-deploy.log"

echo ""
echo "⏳ Build Next.js (site permanece no ar com build anterior; ~10–25 min no ARM)"
echo "   Log: ~/apps/moday-frontend/build-deploy.log"
echo ""

set +e
timeout 3600 npm run build:deploy > "$LOG" 2>&1
BUILD_EXIT=$?
set -e

if [ "$BUILD_EXIT" -ne 0 ]; then
  echo "❌ Build falhou (exit $BUILD_EXIT). Últimas linhas:"
  tail -60 "$LOG"
  exit 1
fi

if [ ! -f .next/standalone/server.js ]; then
  echo "❌ server.js não encontrado após o build"
  tail -60 "$LOG"
  exit 1
fi

echo "🚀 Reiniciando frontend (systemd ou fallback)..."
if [ -f /etc/systemd/system/moday-frontend.service ]; then
  sudo systemctl restart moday-frontend
else
  pkill -f 'node server.js' 2>/dev/null || true
  sleep 1
  cd .next/standalone
  nohup env PORT=3000 HOSTNAME=0.0.0.0 node server.js >> ~/apps/moday-frontend/frontend.log 2>&1 &
fi
sleep 6

for path in / /auth/login; do
  code=$(curl -s -o /dev/null -w "%{http_code}" "http://127.0.0.1:3000${path}" || echo "000")
  echo "Frontend local ${path} → HTTP ${code}"
  [ "$code" = "200" ] || exit 1
done

code80=$(curl -s -o /dev/null -w "%{http_code}" http://127.0.0.1:80/ || echo "000")
echo "Nginx :80 → HTTP ${code80}"
[[ "$code80" = "200" || "$code80" = "301" || "$code80" = "302" ]] || exit 1

echo "✅ Frontend pronto"
ENDSSH
}

deploy_production_scripts_remote() {
  ssh_run 'bash -s' << 'ENDSSH'
set -e
mkdir -p ~/apps/moday/scripts/production
ENDSSH
  scp ${SSH_OPTS} "${SCRIPT_DIR}/scripts/production/"* "${ORACLE_USER}@${ORACLE_IP}:~/apps/moday/scripts/production/"
  ssh_run 'bash -s' << 'ENDSSH'
set -e
sed -i 's/\r$//' ~/apps/moday/scripts/production/* 2>/dev/null || true
chmod +x ~/apps/moday/scripts/production/*.sh
if [ -f ~/apps/moday-frontend/.next/standalone/server.js ]; then
  bash ~/apps/moday/scripts/production/setup-frontend-service.sh
fi
ENDSSH
}

case $OPTION in
  1)
    log_info "Deploy completo..."

    log_info "Empacotando backend..."
    tar -czf "${SCRIPT_DIR}/backend.tar.gz" --exclude='node_modules' --exclude='vendor' --exclude='.git' \
      --exclude='env' --exclude='.env' \
      --exclude='storage/logs/*' --exclude='storage/framework/cache/*' \
      -C "${SCRIPT_DIR}/backend_moday" .

    log_info "Transferindo backend..."
    scp ${SSH_OPTS} "${SCRIPT_DIR}/backend.tar.gz" "${ORACLE_USER}@${ORACLE_IP}:~/apps/moday-backend/"
    rm -f "${SCRIPT_DIR}/backend.tar.gz"

    deploy_backend_remote

    log_info "Empacotando frontend..."
    tar -czf "${SCRIPT_DIR}/frontend.tar.gz" --exclude='node_modules' --exclude='.next' --exclude='.git' \
      -C "${SCRIPT_DIR}/moday_frontend" .

    log_info "Transferindo frontend..."
    scp ${SSH_OPTS} "${SCRIPT_DIR}/frontend.tar.gz" "${ORACLE_USER}@${ORACLE_IP}:~/apps/moday-frontend/"
    rm -f "${SCRIPT_DIR}/frontend.tar.gz"

    deploy_frontend_remote
    deploy_production_scripts_remote
    log_success "Deploy completo concluído!"
    ;;

  2)
    log_info "Deploy apenas Backend..."
    tar -czf "${SCRIPT_DIR}/backend.tar.gz" --exclude='node_modules' --exclude='vendor' --exclude='.git' \
      --exclude='env' --exclude='.env' \
      --exclude='storage/logs/*' -C "${SCRIPT_DIR}/backend_moday" .
    scp ${SSH_OPTS} "${SCRIPT_DIR}/backend.tar.gz" "${ORACLE_USER}@${ORACLE_IP}:~/apps/moday-backend/"
    rm -f "${SCRIPT_DIR}/backend.tar.gz"
    deploy_backend_remote
    log_success "Backend deployado!"
    ;;

  3)
    log_info "Deploy apenas Frontend..."
    tar -czf "${SCRIPT_DIR}/frontend.tar.gz" --exclude='node_modules' --exclude='.next' --exclude='.git' \
      -C "${SCRIPT_DIR}/moday_frontend" .
    scp ${SSH_OPTS} "${SCRIPT_DIR}/frontend.tar.gz" "${ORACLE_USER}@${ORACLE_IP}:~/apps/moday-frontend/"
    rm -f "${SCRIPT_DIR}/frontend.tar.gz"
    deploy_frontend_remote
    deploy_production_scripts_remote
    log_success "Frontend deployado!"
    ;;

  4)
    log_info "Copiando .env..."
    scp ${SSH_OPTS} "${SCRIPT_DIR}/backend_moday/.env.production.example" "${ORACLE_USER}@${ORACLE_IP}:~/apps/moday-backend/.env"
    scp ${SSH_OPTS} "${SCRIPT_DIR}/moday_frontend/.env.production.example" "${ORACLE_USER}@${ORACLE_IP}:~/apps/moday-frontend/.env.production"
    log_success "Arquivos .env atualizados!"
    ;;

  5)
    log_info "Status dos serviços..."
    ssh_run 'bash -s' << 'ENDSSH'
echo "=== Docker ==="
docker ps --format 'table {{.Names}}\t{{.Status}}\t{{.Ports}}' | head -8
echo ""
echo "=== Frontend ==="
pgrep -af "node server.js|next build" || echo "Node/Next não está rodando"
echo ""
echo "=== Portas ==="
ss -tlnp | grep -E ':3000|:8000' || true
echo ""
echo "=== HTTP local ==="
curl -s -o /dev/null -w "frontend:3000 → %{http_code}\n" http://127.0.0.1:3000/ || echo "frontend: falhou"
curl -s -o /dev/null -w "backend:8000 → %{http_code}\n" http://127.0.0.1:8000/api/public/plans || echo "backend: falhou"
echo ""
echo "=== Últimas linhas build-deploy.log ==="
tail -15 ~/apps/moday-frontend/build-deploy.log 2>/dev/null || echo "(sem log de build)"
ENDSSH
    ;;

  0)
    log_info "Saindo..."
    exit 0
    ;;

  *)
    log_error "Opção inválida: $OPTION"
    exit 1
    ;;
esac

echo ""
log_success "Operação concluída!"
log_info "Acesse: http://${ORACLE_IP}"
