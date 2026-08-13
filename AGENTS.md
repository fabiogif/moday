# AGENTS — Moday

Convenções para agentes (Cursor) neste repositório.

## Onde criar arquivos

### Markdown (`.md`)

- **Não** criar na raiz nem soltos em `frontend/` (exceto `README.md` e este `AGENTS.md` na raiz; READMEs colocalizados com testes ok).
- Colocar em `docs/<tema>/` conforme o tema (ver `.cursor/rules/docs-and-scripts-placement.mdc`).
- Índice canônico: `docs/README.md`.

### Shell (`.sh`)

- Testes / smoke / diagnóstico → `scripts/test/`
- Docker / Composer / Reverb / utilitários Laravel → `backend/scripts/`
- Bootstrap obrigatório conforme a rule `docs-and-scripts-placement`.

## Stack

- `frontend/` — Next.js
- `backend/` — Laravel API
- `docs/` — documentação por tema
- `scripts/` — scripts de teste

## Rules Cursor

Arquivos em `.cursor/rules/`:

- `docs-and-scripts-placement.mdc` (always apply)
- `markdown-docs.mdc` (ao editar `docs/**/*.md`)
- `shell-scripts.mdc` (ao editar `scripts/**/*.sh` e `backend/scripts/**/*.sh`)
