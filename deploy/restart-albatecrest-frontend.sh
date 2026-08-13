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

stop_port() {
  if command -v fuser >/dev/null 2>&1; then
    fuser -k "${PORT}/tcp" 2>/dev/null || true
  fi
  if command -v lsof >/dev/null 2>&1; then
    # shellcheck disable=SC2046
    kill $(lsof -ti ":${PORT}" 2>/dev/null) 2>/dev/null || true
  fi
  if [ -f "$PIDFILE" ]; then
    kill "$(cat "$PIDFILE")" 2>/dev/null || true
    rm -f "$PIDFILE"
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

stop_port
sleep 1

set -a
# shellcheck disable=SC1091
[ -f "${FRONTEND_APP_DIR}/.env.production" ] && . "${FRONTEND_APP_DIR}/.env.production"
set +a
export NODE_ENV=production
export PORT
export HOSTNAME="${HOSTNAME:-0.0.0.0}"

cd "$STANDALONE"
setsid "$NODE_BIN" server.js >>"$LOG" 2>&1 < /dev/null &
echo $! > "$PIDFILE"
sleep 5

if ! kill -0 "$(cat "$PIDFILE")" 2>/dev/null; then
  echo "Processo do frontend morreu ao iniciar. Ultimas linhas do log:" >&2
  tail -n 40 "$LOG" >&2 || true
  exit 1
fi

echo "Frontend PID $(cat "$PIDFILE") na porta ${PORT}"
