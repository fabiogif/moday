#!/bin/bash
# Instala/configura a cron do Laravel Scheduler no servidor.
# A linha recomendada roda `php artisan schedule:run` a cada minuto; o Scheduler
# dispara os jobs agendados no horário correspondente (ex.: CollectTenantMetrics
# às 23:00 — ver app/Console/Kernel.php -> collect-tenant-metrics).
#
# USO:  ./backend/scripts/setup-cron.sh [install|check|remove] [--sudo]
#
#  install - adiciona a linha do scheduler ao crontab (idempotente)
#  check   - mostra o status atual da cron
#  remove  - remove a linha do scheduler do crontab
#
# Opção --sudo: aplica a cron ao crontab de root (útil quando o worker roda
#                          como outro usuário / contêiner).
#
# Após instalar, o job das 23:00 executa sozinho. Para validar:
#   php artisan schedule:list   (lista os agendamentos com os horários)
#   php artisan schedule:test   (dispara agendamentos marcados como endOfHour etc.)

set -e

cd "$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)" || exit 1

GREEN='\033[0;32m'
YELLOW='\033[1;33m'
RED='\033[0;31m'
NC='\033[0m'

MODE="${1:-install}"
AS_ROOT=false
[ "$2" = "--sudo" ] && AS_ROOT=true

APP_DIR="$(pwd)"
SCHEDULE_LINE="* * * * * cd $APP_DIR && php artisan schedule:run >> /dev/null 2>&1"

if ! command -v php >/dev/null 2>&1; then
    echo -e "${RED}❌ php não está no PATH do usuário cron. Use '--sudo' ou adicione o PATH (ex. PATH=/usr/local/bin:/usr/bin:/bin).${NC}"
    exit 1
fi

CRONCMD=(crontab)
[ "$AS_ROOT" = true ] && CRONCMD=(crontab -u root)

has_entry() {
    "${CRONCMD[@]}" -l 2>/dev/null | grep -Fq "$APP_DIR && php artisan schedule:run"
}

install() {
    if has_entry; then
        echo -e "${YELLOW}ℹ️  A cron do scheduler já está instalada para $APP_DIR.${NC}"
        check
        exit 0
    fi
    ( "${CRONCMD[@]}" -l 2>/dev/null; echo "$SCHEDULE_LINE" ) | "${CRONCMD[@]}" -
    echo -e "${GREEN}✅ Cron do Laravel Scheduler instalada:${NC}"
    echo "   $SCHEDULE_LINE"
    echo -e "${YELLOW}   O job 'collect-tenant-metrics' (23:00) disparará a partir do próximo minuto.${NC}"
    check
}

remove() {
    if ! has_entry; then
        echo -e "${YELLOW}ℹ️  Nenhuma linha do scheduler encontrada — nada a remover.${NC}"
        exit 0
    fi
    "${CRONCMD[@]}" -l 2>/dev/null | grep -Fv "$APP_DIR && php artisan schedule:run" | "${CRONCMD[@]}" -
    echo -e "${GREEN}✅ Cron do scheduler removida.${NC}"
}

check() {
    echo -e "${YELLOW}📋 Cron atual ($([ "$AS_ROOT" = true ] && echo "root" || echo "usuário atual")):${NC}"
    "${CRONCMD[@]}" -l 2>/dev/null | grep -F "schedule:run" || echo -e "${RED}   (sem linha do scheduler)${NC}"
    echo ""
    echo -e "${YELLOW}🗓  Agendamentos do Laravel (resumo):${NC}"
    php artisan schedule:list 2>/dev/null | grep -E 'collect-tenant-metrics|23:00' || php artisan schedule:list 2>/dev/null | head -20
}

case "$MODE" in
    install) install ;;
    check)   check ;;
    remove)  remove ;;
    help|-h|--help)
        head -14 "$0" | sed 's/^# \{0,1\}//' ;;
    *)
        echo -e "${RED}Uso: $0 [install|check|remove] [--sudo]${NC}" >&2
        exit 1 ;;
esac