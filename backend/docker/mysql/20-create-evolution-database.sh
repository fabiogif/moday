#!/usr/bin/env bash
set -euo pipefail

# Roda só em volume MySQL novo (docker-entrypoint-initdb.d).
# Em ambientes já existentes, rode o mesmo SQL manualmente uma vez.

mysql -uroot -p"${MYSQL_ROOT_PASSWORD}" <<-EOSQL
    CREATE DATABASE IF NOT EXISTS \`evolution_api\` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
    GRANT ALL PRIVILEGES ON \`evolution_api\`.* TO '${MYSQL_USER}'@'%';
    FLUSH PRIVILEGES;
EOSQL
