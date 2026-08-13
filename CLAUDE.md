# Instruções do Projeto — Moday

Antes de realizar qualquer alteração no código consulte obrigatoriamente **todos** os arquivos `.md` presentes em `docs/specs/`, incluindo (mas não se limitando a) qualquer novo arquivo adicionado futuramente à pasta:

- docs/specs/architecture.md
- docs/specs/backend.md
- docs/specs/frontend.md
- docs/specs/coding-standards.md
- docs/specs/design-patterns.md
- docs/specs/security.md
- docs/specs/performance.md
- docs/specs/testing.md
- docs/specs/modules.md
- docs/specs/naming.md
- docs/specs/glossary.md
- docs/specs/audit.md
- docs/specs/engineering-protocol.md
- docs/specs/graphify.md
- docs/specs/laravel.md
- docs/specs/react.md
- docs/specs/moday.md

Este arquivo também é lido pelo Cursor via `.cursor/rules/project-specs.mdc`, que aponta para a mesma pasta `docs/specs/` — mantenha as duas fontes em sincronia; não duplique conteúdo de arquitetura em `.cursor/rules/`.

## Estrutura real do repositório

Monorepo simples com dois diretórios de aplicação (não confundir com nomes de projetos maiores — não existe `backend_moday/`, `moday_frontend/` nem app mobile neste checkout):

- `backend/` — API Laravel 11 (PHP 8.2)
- `frontend/` — Next.js App Router (React 19, TypeScript)
- `docs/` — documentação organizada por tema (`docs/specs/` é a única pasta normativa; o resto é histórico)
- `scripts/` — scripts shell utilitários (`scripts/test/`, `backend/scripts/`)

## Regras

- Sempre siga a arquitetura existente (descrita em `docs/specs/architecture.md`, `backend.md`, `frontend.md` — baseada em código real, não em aspiração).
- Nunca crie novos padrões sem necessidade.
- Sempre reutilize implementações existentes.
- Respeite SOLID, DRY, KISS e YAGNI — como princípios de leitura do padrão já existente, não como justificativa para introduzir Clean Architecture ou camadas que o código não usa.
- Antes de concluir qualquer implementação execute mentalmente a auditoria definida em `docs/specs/audit.md`.
- Corrija automaticamente problemas identificados antes de finalizar a implementação.

## Claude Project Instructions

This project uses modular specifications.

Load and follow all specifications located at:

docs/specs/

Mandatory specifications:

- docs/specs/graphify.md
- docs/specs/laravel.md
- docs/specs/react.md
- docs/specs/moday.md
- docs/specs/engineering-protocol.md (Medium/High planning)
- docs/specs/audit.md (pre-completion checklist)

All instructions are cumulative.

If two instructions conflict, follow this priority:

1. moday.md
2. laravel.md / react.md
3. graphify.md

## graphify

Cada app tem seu próprio grafo: `backend/graphify-out/` e `frontend/graphify-out/` (o do frontend só existe depois de rodar `/graphify frontend` pela primeira vez). **Não há merge multi-repo** — não existe workflow de `graphify merge-graphs` neste projeto, porque não há múltiplos repos separados a mesclar.

Rules:
- Para perguntas sobre o backend, rode `graphify query "<pergunta>"` (ou `path`/`explain`) a partir de `backend/`, usando `backend/graphify-out/graph.json`.
- Para perguntas sobre o frontend, rode a partir de `frontend/`; se `frontend/graphify-out/` ainda não existir, rode `/graphify` nessa pasta primeiro.
- Use `graphify path "<A>" "<B>"` para relações e `graphify explain "<concept>"` para conceitos focados — retornam um subgrafo escopado, normalmente bem menor que `GRAPH_REPORT.md` ou grep bruto.
- Leia `graphify-out/GRAPH_REPORT.md` só para revisão ampla de arquitetura, ou quando `query`/`path`/`explain` não trouxerem contexto suficiente.
- **Depois de modificar código em `backend/` ou `frontend/`**, rode a partir da pasta do app modificado:
  ```
  graphify update .
  ```
  (AST-only quando as mudanças forem só código — sem custo de API. Se docs/imagens também mudaram, roda a extração semântica normal.)
