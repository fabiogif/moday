#!/usr/bin/env bash
# Backup diário do banco MySQL de produção (container moday-mysql) via mysqldump.
# Sincronizado automaticamente pro servidor a cada deploy do backend (rsync de backend/).
# Instalação (uma vez): ver moday-db-backup.service / moday-db-backup.timer neste diretório.
set -euo pipefail

APP_DIR="/home/ubuntu/apps/moday-backend"
BACKUP_DIR="/home/ubuntu/apps/backups/moday"
RETENTION_DAYS=30
TIMESTAMP="$(date +%Y%m%d_%H%M%S)"

mkdir -p "$BACKUP_DIR"

DB_DATABASE="$(grep '^DB_DATABASE=' "$APP_DIR/.env" | cut -d= -f2-)"
DB_USERNAME="$(grep '^DB_USERNAME=' "$APP_DIR/.env" | cut -d= -f2-)"
DB_PASSWORD="$(grep '^DB_PASSWORD=' "$APP_DIR/.env" | cut -d= -f2-)"

OUT_FILE="$BACKUP_DIR/moday_${DB_DATABASE}_${TIMESTAMP}.sql.gz"
TMP_FILE="${OUT_FILE}.tmp"

docker exec -e MYSQL_PWD="${DB_PASSWORD}" moday-mysql \
  mysqldump -u"${DB_USERNAME}" --single-transaction --routines --triggers "${DB_DATABASE}" \
  | gzip > "$TMP_FILE"

mv "$TMP_FILE" "$OUT_FILE"
echo "Backup criado: $OUT_FILE ($(du -h "$OUT_FILE" | cut -f1))"

find "$BACKUP_DIR" -name 'moday_*.sql.gz' -mtime "+${RETENTION_DAYS}" -print -delete
