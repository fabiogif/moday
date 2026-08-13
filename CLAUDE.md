# Instruções do Projeto

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

## Regras

- Sempre siga a arquitetura existente.
- Nunca crie novos padrões sem necessidade.
- Sempre reutilize implementações existentes.
- Respeite SOLID, DRY, KISS e YAGNI.
- Antes de concluir qualquer implementação execute mentalmente a auditoria definida em docs/specs/audit.md.
- Corrija automaticamente problemas identificados antes de finalizar a implementação.

# Claude Project Instructions

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

This project has a knowledge graph at graphify-out/ with god nodes, community structure, and cross-file relationships.

**IMPORTANT — this graph is a merge of 3 separate app repos, not a scan of the wrapper root.** `backend_moday/`, `moday_frontend/` and `moday_mobile/` are gitignored at the root and each has its own `graphify-out/`. The root `graphify-out/graph.json` is produced by `graphify merge-graphs`. Never run `graphify update .` (or extract) at the project root — the root itself has almost no application code, so it silently rebuilds graph.json from a near-empty scan and clobbers the merged graph.

Rules:
- For codebase questions, first run `graphify query "<question>"` when graphify-out/graph.json exists. Use `graphify path "<A>" "<B>"` for relationships and `graphify explain "<concept>"` for focused concepts. These return a scoped subgraph, usually much smaller than GRAPH_REPORT.md or raw grep output.
- If graphify-out/wiki/index.md exists, use it for broad navigation instead of raw source browsing.
- Read graphify-out/GRAPH_REPORT.md only for broad architecture review or when query/path/explain do not surface enough context.
- **After modifying code in any of the 3 apps, run this from the project root** (AST-only, no API cost):
  ```
  graphify update ./backend_moday
  graphify update ./moday_frontend
  graphify update ./moday_mobile
  graphify merge-graphs ./backend_moday/graphify-out/graph.json ./moday_frontend/graphify-out/graph.json ./moday_mobile/graphify-out/graph.json --out graphify-out/graph.json
  ```
  You only need to run `update` for the app(s) actually touched, but always re-run the `merge-graphs` step afterward so the root graph reflects it.
