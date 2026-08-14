#!/bin/bash
# Rotaciona credenciais locais expostas na auditoria de segurança (2026-08-14)
# e orienta a rotação das que dependem de painel externo.
#
# USO (a partir de qualquer lugar; entra sozinho em backend/):
#   ./backend/scripts/rotate-credentials.sh [ENV_FILE]
#
# ENV_FILE: arquivo .env a editar (padrão: .env; ex.: env)
#
# O QUE FAZ:
#   - Backup do ENV_FILE (ENV_FILE.bak.<timestamp>)
#   - Sorteia novos APP_KEY, JWT_SECRET, REVERB_APP_ID/KEY/SECRET
#   - Gera nova chave SSH de deploy (id_deploy_ed25519 / .pub)
#   - Imprime o checklist manual para o que NÃO pode ser rotacionado no arquivo
#     (Mercado Pago, SMTP/Brevo, Evolution API) e para o servidor de produção.
#
# NÃO faz push, NÃO altera o servidor nem painéis externos.

set -e

cd "$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)" || exit 1

ENV_FILE="${1:-.env}"

if [ ! -f "$ENV_FILE" ]; then
    echo "❌ '$ENV_FILE' não existe em backend/."
    echo "   Uso: ./backend/scripts/rotate-credentials.sh [ENV_FILE]"
    echo "   Ex.: ./backend/scripts/rotate-credentials.sh env"
    exit 1
fi

TS="$(date +%Y%m%d%H%M%S)"
BACKUP="${ENV_FILE}.bak.${TS}"
cp "$ENV_FILE" "$BACKUP"
echo "✅ Backup criado: $BACKUP"

# Geradores de valor aleatório (openssl presente no git bash; php como fallback)
gen_b64() {
    if command -v openssl >/dev/null 2>&1; then
        openssl rand -base64 "$1" | tr -d '\n'
    else
        php -r "echo base64_encode(random_bytes($1));"
    fi
}
gen_hex() { openssl rand -hex "$1" 2>/dev/null || php -r "echo bin2hex(random_bytes($1));"; }
gen_num() { php -r "echo random_int(100000,999999);" 2>/dev/null || echo $((RANDOM + RANDOM % 900000)); }

NEW_APP_KEY="base64:$(gen_b64 32)"
NEW_JWT_SECRET="$(gen_b64 64)"
NEW_REVERB_ID="$(gen_num)"
NEW_REVERB_KEY="$(gen_hex 20)"
NEW_REVERB_SECRET="$(gen_hex 32)"

echo ""
echo "⚠️  Novos valores a aplicar em '$ENV_FILE':"
echo "   APP_KEY            = $NEW_APP_KEY"
echo "   JWT_SECRET         = $NEW_JWT_SECRET"
echo "   REVERB_APP_ID      = $NEW_REVERB_ID"
echo "   REVERB_APP_KEY     = $NEW_REVERB_KEY"
echo "   REVERB_APP_SECRET  = $NEW_REVERB_SECRET"

read -r -p "Aplicar (digite: sim)? " CONFIRM
if [ "$CONFIRM" != "sim" ]; then
    echo "Cancelado. Backup preservado em $BACKUP."
    exit 0
fi

# Substitui os valores no ENV_FILE (base64 nunca contém '#' no separador do sed)
sed -i "s#^APP_KEY=.*#APP_KEY=${NEW_APP_KEY}#" "$ENV_FILE"
sed -i "s#^JWT_SECRET=.*#JWT_SECRET=${NEW_JWT_SECRET}#" "$ENV_FILE"
sed -i "s#^REVERB_APP_ID=.*#REVERB_APP_ID=${NEW_REVERB_ID}#" "$ENV_FILE"
sed -i "s#^REVERB_APP_KEY=.*#REVERB_APP_KEY=${NEW_REVERB_KEY}#" "$ENV_FILE"
sed -i "s#^REVERB_APP_SECRET=.*#REVERB_APP_SECRET=${NEW_REVERB_SECRET}#" "$ENV_FILE"

echo "✅ '$ENV_FILE' atualizado."

# Nova chave SSH para deploy (nome casa com o gitignore 'id_*')
SSH_DIR="${SSH_DIR:-$PWD}"
if [ ! -f "$SSH_DIR/id_deploy_ed25519" ]; then
    ssh-keygen -t ed25519 -a 100 -N "" -f "$SSH_DIR/id_deploy_ed25519" >/dev/null
    echo "✅ Nova chave SSH gerada em: $SSH_DIR/id_deploy_ed25519.pub"
fi

echo ""
echo "================ CHECKLIST MANUAL (não rotacionável por script) ================"
echo ""
echo "1) MERCADO PAGO  — painel https://www.mercadopago.com.br/developers"
echo "   → Regenerar ACCESS_TOKEN e WEBHOOK_SECRET; a chave pública pode ser renovada."
echo "   → Atualizar no .env do servidor: MERCADOPAGO_ACCESS_TOKEN, MERCADOPAGO_PUBLIC_KEY,"
echo "     MERCADOPAGO_WEBHOOK_SECRET (no servidor também atualizar o webhook no painel)."
echo ""
echo "2) SMTP / BREVO  — painel Brevo"
echo "   → Regenerar a senha SMTP (login b4162a001@smtp-brevo.com) e atualizar MAIL_PASSWORD."
echo ""
echo "3) EVOLUTION API (WhatsApp) — painel da instância EVOLUTION_API_KEY."
echo ""
echo "4) CHAVE SSH ANTIGA (id_ed25520 comprometida)"
echo "   → Revogue/remova a chave pública antiga do GitLab/servidor de deploy."
echo "   → Publique a chave nova: cat \"$SSH_DIR/id_deploy_ed25519.pub\""
echo ""
echo "5) SERVIDOR DE PRODUÇÃO (.env do deploy)"
echo "   → Copiar os novos APP_KEY / JWT_SECRET / REVERB_* para o .env do servidor,"
echo "     recarregar config e reiniciar filas/workers (php artisan config:clear, queue:restart)."
echo "   → ATENÇÃO: com JWT/APP_KEY novos, tokens JWT/caches existentes serão invalidados."
echo ""
echo "6) APÓS A ROTAÇÃO  — force-push do histórico purgado (clone 'moday-purgado'):"
echo "   cd ../moday-purgado"
echo "   git remote add origin https://gitlab.albatec.com.br/root/albatecrest"
echo "   git push origin --force --all --tags"
echo ""
echo "7) REVOGAR ACESSO — considerar invalidar sessões/tokens e avisar a equipe para re-clonar."
echo "================================================================================"