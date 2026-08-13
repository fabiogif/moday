#!/usr/bin/env bash
# Reinicia o frontend RestTec sem pedir senha no CI.
# 1) sudo -n + systemd do sistema, se o runner tiver NOPASSWD
# 2) senão, mata a porta e sobe o standalone com setsid (sobrevive ao fim do job)
set -euo pipefail

FRONTEND_APP_DIR="${FRONTEND_APP_DIR:-${HOME}/apps/albatecrest-frontend}"
PORT="${FRONTEND_PORT:-3002}"
LOG="${FRONTEND_APP_DIR}/frontend.log"
PIDFILE="${FRONTEND_APP_DIR}/frontend.pid"
STANDALONE="${FRONTEND_APP_DIR}/.next/standalone"
UNIT_SRC="${CI_PROJECT_DIR:-}/deploy/albatecrest-frontend.service"
NODE_BIN="${NODE_BIN:-$(command -v node || echo /usr/bin/node)}"

port_in_use() {
  if command -v ss >/dev/null 2>&1; then
    ss -lptn "sport = :${PORT}" 2>/dev/null | grep -q ":${PORT}"
    return $?
  fi
  if command -v lsof >/dev/null 2>&1; then
    lsof -iTCP:"${PORT}" -sTCP:LISTEN >/dev/null 2>&1
    return $?
  fi
  return 1
}

stop_port() {
  if [ -f "$PIDFILE" ]; then
    kill "$(cat "$PIDFILE")" 2>/dev/null || true
    kill -9 "$(cat "$PIDFILE")" 2>/dev/null || true
    rm -f "$PIDFILE"
  fi

  # Processos do standalone anterior (mesmo com cwd diferente)
  pkill -f "${FRONTEND_APP_DIR}/.next/standalone/server.js" 2>/dev/null || true
  pkill -f "node server.js" 2>/dev/null || true

  if command -v fuser >/dev/null 2>&1; then
    fuser -k "${PORT}/tcp" 2>/dev/null || true
  fi

  if command -v lsof >/dev/null 2>&1; then
    # shellcheck disable=SC2046
    kill -9 $(lsof -tiTCP:"${PORT}" -sTCP:LISTEN 2>/dev/null) 2>/dev/null || true
  fi

  if command -v ss >/dev/null 2>&1; then
    # Extrai PIDs de ss -lptn (users:(("node",pid=123,...)))
    pids=$(ss -lptn "sport = :${PORT}" 2>/dev/null | sed -n 's/.*pid=\([0-9]\+\).*/\1/p' | sort -u)
    for pid in $pids; do
      kill -9 "$pid" 2>/dev/null || true
    done
  fi
}

if sudo -n true 2>/dev/null; then
  if [ -f "$UNIT_SRC" ]; then
    sudo -n cp "$UNIT_SRC" /etc/systemd/system/albatecrest-frontend.service
  fi
  sudo -n systemctl daemon-reload
  sudo -n systemctl enable albatecrest-frontend.service
  sudo -n systemctl restart albatecrest-frontend.service
  sleep 5
  sudo -n systemctl is-active albatecrest-frontend.service
  exit 0
fi

echo "sudo NOPASSWD indisponivel - reiniciando o frontend na porta ${PORT} sem systemd de sistema"

if [ ! -f "${STANDALONE}/server.js" ]; then
  echo "Nao encontrei ${STANDALONE}/server.js (rode npm run build:deploy antes)" >&2
  exit 1
fi

for _ in 1 2 3 4 5 6; do
  stop_port
  sleep 1
  if ! port_in_use; then
    break
  fi
  echo "Porta ${PORT} ainda em uso - tentando novamente..."
done

if port_in_use; then
  echo "Nao foi possivel liberar a porta ${PORT}" >&2
  if command -v ss >/dev/null 2>&1; then
    ss -lptn "sport = :${PORT}" >&2 || true
  fi
  exit 1
fi

set -a
# shellcheck disable=SC1091
[ -f "${FRONTEND_APP_DIR}/.env.production" ] && . "${FRONTEND_APP_DIR}/.env.production"
set +a
export NODE_ENV=production
export PORT
# Força bind em todas as interfaces; HOSTNAME do .env pode ser 127.0.1.1 e causar EADDRINUSE
export HOSTNAME=0.0.0.0

cd "$STANDALONE"
setsid "$NODE_BIN" server.js >>"$LOG" 2>&1 < /dev/null &
echo $! > "$PIDFILE"
sleep 5

if ! kill -0 "$(cat "$PIDFILE")" 2>/dev/null; then
  echo "Processo do frontend morreu ao iniciar. Ultimas linhas do log:" >&2
  tail -n 40 "$LOG" >&2 || true
  exit 1
fi

echo "Frontend PID $(cat "$PIDFILE") na porta ${PORT} (HOSTNAME=0.0.0.0)"
