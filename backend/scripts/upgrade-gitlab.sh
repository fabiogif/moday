#!/bin/bash
set -euo pipefail

# ==============================================================================
# Script: upgrade-gitlab.sh
# Destino no servidor: /home/ubuntu/apps/gitlab/upgrade.sh
# Uso: ./upgrade.sh <versao-alvo>
# Exemplo: ./upgrade.sh 19.2.7-ce.0
# ==============================================================================

GITLAB_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
cd "$GITLAB_DIR" || exit 1

TARGET_TAG="${1:-}"

if [[ -z "$TARGET_TAG" ]]; then
    echo "ERRO: Informe a versão alvo do GitLab CE."
    echo "Uso: $0 <versao-alvo>"
    echo "Exemplo: $0 19.2.7-ce.0"
    exit 1
fi

TIMESTAMP="$(date +%Y%m%d_%H%M%S)"
BACKUP_DIR="/home/ubuntu/gitlab-pre-upgrade-backup_${TIMESTAMP}"

echo "========================================================================"
echo " 1. Verificações pré-upgrade"
echo "========================================================================"

CURRENT_IMAGE=$(grep -E '^\s*image:\s*gitlab/gitlab-ce:' docker-compose.yml | awk '{print $2}')
echo "Imagem atual no compose: $CURRENT_IMAGE"
echo "Imagem alvo desejada:     gitlab/gitlab-ce:${TARGET_TAG}"

if [[ "$CURRENT_IMAGE" == "gitlab/gitlab-ce:${TARGET_TAG}" ]]; then
    echo "AVISO: A versão alvo já é a mesma configurada no docker-compose.yml."
    read -r -p "Deseja forçar o pull e restart mesmo assim? [y/N] " CONFIRM
    if [[ ! "$CONFIRM" =~ ^[Yy]$ ]]; then
        echo "Operação abortada."
        exit 0
    fi
fi

if ! docker ps --format '{{.Names}}' | grep -q '^gitlab$'; then
    echo "ERRO: Contêiner 'gitlab' não está em execução."
    exit 1
fi

echo "Verificando se há migrações de banco pendentes em background..."
QUEUED_MIGRATIONS=$(docker exec gitlab gitlab-rails runner -e production \
    'puts Gitlab::Database::BackgroundMigration::BatchedMigration.queued.count' 2>/dev/null || echo "0")

if [[ "$QUEUED_MIGRATIONS" != "0" ]]; then
    echo "ERRO: Existem $QUEUED_MIGRATIONS migrações de banco em background ainda na fila."
    echo "Aguarde a finalização das migrações antes de realizar o upgrade de versão."
    exit 1
fi
echo "✓ Nenhuma migração pendente em background."

echo "========================================================================"
echo " 2. Backup de segurança de configurações"
echo "========================================================================"
mkdir -p "$BACKUP_DIR"
cp -a config/gitlab-secrets.json config/gitlab.rb docker-compose.yml "$BACKUP_DIR/"
echo "✓ Arquivos essenciais salvos em: $BACKUP_DIR"

echo "========================================================================"
echo " 3. Atualizando docker-compose.yml"
echo "========================================================================"
cp docker-compose.yml "docker-compose.yml.bak-${TIMESTAMP}"
sed -i -E "s|(image:\s*gitlab/gitlab-ce:).*|\1${TARGET_TAG}|" docker-compose.yml
echo "✓ docker-compose.yml atualizado para gitlab/gitlab-ce:${TARGET_TAG}"

echo "========================================================================"
echo " 4. Baixando nova imagem Docker"
echo "========================================================================"
docker compose pull gitlab

echo "========================================================================"
echo " 5. Reiniciando contêiner GitLab"
echo "========================================================================"
docker compose up -d gitlab

echo "========================================================================"
echo " 6. Acompanhamento de inicialização"
echo "========================================================================"
echo "GitLab atualizado com sucesso. Aguardando inicialização dos serviços..."
echo "Pressione Ctrl+C para sair do log a qualquer momento (o contêiner continuará rodando)."
sleep 5
docker logs -f --tail 100 gitlab
