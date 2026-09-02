#!/bin/bash
# Atualiza nome/preço dos planos Básico/Profissional/Enterprise em produção
# para Sabor Start/Pro/Chef (R$ 39,90 / 59,90 / 79,90).
#
# USO (rodar NO SERVIDOR de produção, a partir de qualquer lugar; entra
# sozinho em backend/):
#   ./backend/scripts/update-plan-pricing.sh [ENV_FILE]
#
# ENV_FILE: arquivo .env de onde ler as credenciais do banco (padrão: .env)
#
# O QUE FAZ:
#   - Mostra os valores atuais dos 3 planos (por url, que não muda)
#   - Pede confirmação explícita
#   - Faz UPDATE de name/price via mysql client, casando por url
#     (não mexe na url, então nenhum link/registro existente quebra)
#   - Mostra os valores finais para conferência
#
# NÃO roda seeders, NÃO altera a url dos planos, NÃO reinicia serviços.

set -e

cd "$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)" || exit 1

ENV_FILE="${1:-.env}"

if [ ! -f "$ENV_FILE" ]; then
    echo "❌ '$ENV_FILE' não existe em backend/."
    echo "   Uso: ./backend/scripts/update-plan-pricing.sh [ENV_FILE]"
    exit 1
fi

env_get() {
    php -r '
        $lines = file($argv[1], FILE_IGNORE_NEW_LINES);
        foreach ($lines as $line) {
            if (preg_match("/^" . preg_quote($argv[2], "/") . "=(.*)$/", $line, $m)) {
                echo trim($m[1], "\"\x27");
                exit;
            }
        }
    ' "$ENV_FILE" "$1"
}

DB_HOST="$(env_get DB_HOST)"
DB_PORT="$(env_get DB_PORT)"
DB_DATABASE="$(env_get DB_DATABASE)"
DB_USERNAME="$(env_get DB_USERNAME)"
DB_PASSWORD="$(env_get DB_PASSWORD)"
DB_PORT="${DB_PORT:-3306}"

if [ -z "$DB_HOST" ] || [ -z "$DB_DATABASE" ] || [ -z "$DB_USERNAME" ]; then
    echo "❌ Não foi possível ler DB_HOST/DB_DATABASE/DB_USERNAME de '$ENV_FILE'."
    exit 1
fi

if ! command -v mysql >/dev/null 2>&1; then
    echo "❌ Cliente 'mysql' não encontrado no PATH deste servidor."
    exit 1
fi

mysql_run() {
    MYSQL_PWD="$DB_PASSWORD" mysql -h "$DB_HOST" -P "$DB_PORT" -u "$DB_USERNAME" "$DB_DATABASE" "$@"
}

echo "Banco: $DB_USERNAME@$DB_HOST:$DB_PORT/$DB_DATABASE"
echo ""
echo "Valores ATUAIS:"
mysql_run -e "SELECT id, name, price, url FROM plans WHERE url IN ('plano-basico','plano-profissional','plano-enterprise');"

echo ""
read -r -p "Aplicar as mudanças de nome/preço acima (digite: sim)? " CONFIRM
if [ "$CONFIRM" != "sim" ]; then
    echo "Cancelado. Nenhuma alteração aplicada."
    exit 0
fi

mysql_run <<'SQL'
UPDATE plans SET name = 'Sabor Start', price = 39.90 WHERE url = 'plano-basico';
UPDATE plans SET name = 'Sabor Pro',   price = 59.90 WHERE url = 'plano-profissional';
UPDATE plans SET name = 'Sabor Chef',  price = 79.90 WHERE url = 'plano-enterprise';
SQL

echo ""
echo "✅ Atualizado. Valores FINAIS:"
mysql_run -e "SELECT id, name, price, url FROM plans WHERE url IN ('plano-basico','plano-profissional','plano-enterprise');"
