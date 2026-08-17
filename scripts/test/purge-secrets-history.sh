#!/bin/bash
# Purga vazamentos de segredos do histórico do git (relatório de auditoria 2026-08-14).
#
# O QUE FAZ:
#  1. Cria um clone em <repo>-purgado/ — o repositório ORIGINAL não é alterado.
#  2. Roda `git filter-repo` removendo arquivos sensíveis (chaves SSH, .env, env) de TODOS os commits.
#  3. Reescreve valores de segredos (senha, JWT_SECRET, chaves Reverb, Mercado Pago, SMTP) por placeholders.
#  4. Imprime as instruções manuais finais (re-adicionar remote + force-push).
#
# O QUE NÃO FAZ (deliberadamente):
#  - Não faz push. Após revisar o clone, você força o push manualmente.
#  - Não rotaciona credenciais. Você DEVE rotacionar após o push (ver instruções abaixo).
#
# PRÉ-REQUISITO: git-filter-repo
#   pip install git-filter-repo   (ou  brew install git-filter-repo)

set -e

ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/../.." && pwd)"
cd "$ROOT" || exit 1

# 0. Instalação do filter-repo
if ! command -v git-filter-repo >/dev/null 2>&1 && ! git filter-repo --version >/dev/null 2>&1; then
    echo "❌ git-filter-repo não está instalado ou não está no PATH."
    echo "   instale com: pip install git-filter-repo  (ou brew install git-filter-repo)"
    echo "   se o pip instalou em %AppData%\\Python\\Python3xx\\Scripts, adicione ao PATH, ex:"
    echo "   export PATH=\"\$PATH:$(pip show git-filter-repo 2>/dev/null | sed -n 's#^Location: ##p')/Scripts\""
    exit 1
fi

if ! git rev-parse --is-inside-work-tree >/dev/null 2>&1; then
    echo "❌ Não é um repositório git: $ROOT"
    exit 1
fi

ORIGIN_URL="$(git remote get-url origin 2>/dev/null || true)"
DEST="${ROOT}-purgado"

echo "⚠️  Revisão antes de começar:"
echo "   Repo fonte : $ROOT"
echo "   Clone    : $DEST"
echo "   Origin   : ${ORIGIN_URL:-(nenhum)}"
echo ""
echo "⚠️  Após o push, ROTACIONE as credenciais expostas:"
echo "   - JWT_SECRET, APP_KEY, REVERB_APP_SECRET/KEY"
echo "   - Mercado Pago (public key + access token + webhook secret)"
echo "   - SMTP/Brevo (usuario/email + senha)"
echo "   - Gere uma nova chave SSH para deploy e revogue a antiga"
echo ""
read -r -p "Prosseguir com o clone e a purga? (digite: sim) " CONFIRM
if [ "$CONFIRM" != "sim" ]; then
    echo "Cancelado."
    exit 0
fi

# 1. Clone
if [ -e "$DEST" ]; then
    echo "❌ '$DEST' já existe — mova/remova antes de reexecutar."
    exit 1
fi
echo ">> Clonando para $DEST ..."
git clone "$ROOT" "$DEST"
cd "$DEST" || exit 1

# 2. Lista de segredos para substituir em TODO o histórico (arquivo de replace-text)
SECRETS_FILE="$(mktemp)"
cat > "$SECRETS_FILE" <<'EOF'
$Duda0793==>TEST_USER_PASSWORD
0WkxqDHwoc6cuIGgzqoUsbIU8trXgQYuvE7G63Adn0qTLdGlUNHkruQW49kQNbvl==>JWT_SECRET=<ROTACIONAR>
TTOqeJ/vMZxB6yKHZf6M3l6R1E5zC2oC4Uc7NNfpi9Q==>APP_KEY=<ROTACIONAR>
gckv5wihfyan3sinvj8v==>REVERB_APP_SECRET=<ROTACIONAR>
kgntgjptuwjk1elaoq4a==>REVERB_APP_KEY=<ROTACIONAR>
b4162a001@smtp-brevo.com==>SMTP_USERNAME=<DEFINIR_VIA_ENV>
APP_USR-fc56f8b1-f978-4ecb-a377-0e00341632b9==>MP_PUBLIC_KEY=<DEFINIR_VIA_ENV>
EOF

# 3. Purga (remove arquivos + substitui segredos) — só para o clone
git filter-repo --force \
    --invert-paths \
    --path backend/id_ed25520 \
    --path backend/id_ed25520.pub \
    --path backend/.env.bak \
    --path backend/env \
    --path id_ed25520 \
    --path id_ed25520.pub \
    --path env \
    --path .env.bak \
    --path .env.development \
    --path .env.local \
    --path .env.oracle-mysql \
    --path .env.update \
    --path-glob '**/.env.bak' \
    --path-glob '**/id_ed25520*' \
    --path-glob '**/env' \
    --replace-text "$SECRETS_FILE"

rm -f "$SECRETS_FILE"

# 4. Verificação pós-purga
echo ""
echo ">> Verificação no clone purgado:"
if [ "$(git grep -rIl 'Duda0793\|smtp-brevo\|APP_USR-fc56f8b1\|SK-0WkxqDHwoc6cu' "$(git rev-list --all | head -1)" 2>/dev/null | wc -l)" -gt 0 ]; then
    echo "⚠️  Ainda há segredos no working tree da HEAD. Revise manualmente."
else
    echo "✅ Nenhum segredo encontrado no working tree da HEAD."
fi
git log --all --oneline | sort -u | wc -l | xargs echo "ℹ️  Commits reescritos:"
echo "ℹ️  Arquivos sensíveis restantes na HEAD:"
git ls-files | grep -iE 'id_ed25520|\.env\.bak|(^|/)env$' || echo "   (nenhum)"

echo ""
echo ">> PRÓXIMOS PASSOS (manuais, com o clone \"$DEST\"):"
if [ -n "$ORIGIN_URL" ]; then
    echo "   cd $DEST"
    echo "   git remote add origin $ORIGIN_URL"
fi
echo "   git push origin --force --all --tags"
echo ""
echo ">> OBRA COMPLETA — em seguida, em qualquer ambiente/conta:"
echo "   - Rotacione TODAS as credenciais citadas no aviso inicial."
echo "   - Avise a equipe para re-clonar (git pull falhará após reescrita)."
echo "   - Considere instalar Gitleaks/git-secrets + bloqueio de push no servidor."