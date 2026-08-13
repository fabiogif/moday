# Protocolo de Engenharia — Moday

Processo obrigatório para mudanças **Medium** e **High/Critical**, e framework de orquestração de agentes para quando a tarefa justificar mais de um agente.
Complementa (não substitui) `docs/specs/audit.md`: este arquivo cobre o **antes** (investigar → estratégia → plano → aprovação); o audit cobre o **depois** (checklist de fechamento).

Prioridade se houver conflito: `moday.md` → `laravel.md` / `react.md` → `graphify.md` → este protocolo.

Não existe `AGENTS.md` neste projeto (verificar antes de assumir). Não invente Clean Architecture / DDD / UseCases / service-facade sem precedente no módulo — hoje **nenhum** módulo usa esses padrões.

---

## 0. Fontes de verdade

| Prioridade | Fonte |
|------------|--------|
| 1 | `docs/specs/moday.md` |
| 2 | `docs/specs/laravel.md` / `react.md` |
| 3 | `docs/specs/graphify.md` |
| 4 | Demais `docs/specs/*` (e `.cursor/rules/project-specs.mdc`, que aponta para a mesma pasta) |
| 5 | `CLAUDE.md` |
| 6 | Código do **módulo vizinho** maduro |

Se documentação e implementação divergirem: reporte a inconsistência e siga o padrão predominante do código (não a exceção, e não o que a spec diz se o código já mudou).

---

## 1. Investigar antes de perguntar

Nunca pergunte o que o repositório já responde.

### Graphify primeiro

Antes de exploração ampla (`Read` / `Grep` / `Glob`), a partir da pasta do app relevante (`backend/` ou `frontend/`):

```bash
graphify query "<pergunta>"
graphify path "<A>" "<B>"
graphify explain "<conceito>"
```

Cada app tem seu **próprio** `graphify-out/` — não há merge multi-repo neste projeto (não confundir com setups que têm `backend_moday`/`moday_frontend`/`moday_mobile` separados; aqui é só `backend/` + `frontend/`).

Após alterar código em um app:

```bash
graphify update .
```

(rodado de dentro do app tocado; AST-only quando só código mudou, sem custo de API)

### Também consultar

Specs, `.cursor/rules/`, `composer.json`/`package.json`, `.env.example`, testes, Docker/scripts de deploy, implementação similar no módulo vizinho.

### Não perguntar (já definido)

- Stack: Laravel 11/PHP 8.2 (backend) + Next.js 15/React 19/TypeScript (frontend). Sem app mobile neste repo.
- BE: Routes → Controller → FormRequest → Service → Repository → Model → `ApiResponseClass`. Sem service-facade/sub-services em nenhum domínio hoje.
- FE: Page → Hook (`use-authenticated-api.ts`, nunca `use-api.ts`) → `ApiClient` (`src/lib/api-client.ts`) → componente reutilizável
- Multi-tenant: coluna `tenant_id`, checada manualmente (sem trait `BelongsToTenant`, sem `AuthTenantService`)
- Auth: guards `api`/`client` (JWT); `acl.permission` quando o módulo usa ACL. **Não existe** `plan.feature`/`plan.order_limit`/`plan.user_limit`, nem guard `admin`
- Validação: Form Request (BE) / Zod + RHF (FE)
- Naming e pastas: `docs/specs/naming.md` + módulo vizinho
- CI: não há pipeline configurado neste repo (`.github/` só tem `copilot-instructions.md`) — não assumir GitLab CI nem GitHub Actions rodando testes automaticamente

---

## 2. Análise de arquitetura

Antes de propor solução, identificar no módulo alvo:

- Fluxo Controller → Service → Repository (sempre plano — não há domínio com sub-services para copiar)
- Hook de API / componentes reutilizáveis no FE
- Middleware de auth/tenant/ACL relevante
- Abstrações existentes — **estender, não duplicar**

Nunca criar sem precedente: Entities DDD, UseCase classes, `*ServiceInterface`, DTOs/libs "genéricas", service-facade.

---

## 3. Análise de impacto

| Área | Checar |
|------|--------|
| Direto | Arquivos em `backend/` e/ou `frontend/` |
| Indireto | Dependências via graphify (`graphify path`/`explain`) |
| Compatibilidade | Contrato da API consumido pelo frontend |
| Banco | Migrations, índices, queries, seeders — sem DROP/TRUNCATE destrutivo sem consentimento explícito |
| FE | Pages, hooks, cache do `useAuthenticatedApi` |
| BE | Controllers, Services, Repositories, Events, Middleware, permissões |
| Infra | Docker local, Redis/cache, Reverb (se a mudança envolver realtime) |

Se afetar **auth, ACL, schema, contrato de API pública ou vários módulos**: parar após a análise, explicar riscos e aguardar aprovação.

---

## 4. Estratégia de agentes

Antes de implementar (e antes de decidir se vale a pena usar subagents), classifique a tarefa. **A quantidade de agentes não é definida a priori — use quantos a estrutura da tarefa justificar, e nenhum a mais.**

### Princípios

1. **Architecture Alignment** — a arquitetura de agentes segue a estrutura real da tarefa, não o inverso.
2. **Minimal Agent Principle** — use o mínimo necessário; mais agentes não significa resultado melhor, e coordenação tem custo.
3. **Decomposition Before Parallelization** — só paralelize depois de confirmar que a tarefa é realmente decomponível em partes independentes.
4. **Sequential Dependency Awareness** — se uma etapa depende do resultado de outra (ex.: frontend depende do contrato do endpoint), não paralelize essas etapas.
5. **Shared File Protection** — dois agentes nunca editam o mesmo arquivo ao mesmo tempo. Se `OrderService.php` precisa mudar em duas frentes, isso é uma etapa sequencial ou um único agente, não dois agentes paralelos.
6. **Centralized Verification** — resultado de múltiplos agentes sempre passa por revisão central antes de considerar concluído.
7. **Existing Architecture First** — antes de criar Service/Component/Hook/Repository novo, procurar e reutilizar o que já existe (ver seção 2).
8. **Objective Validation** — nunca considerar uma implementação correta só porque um agente disse "concluído"; validar com testes/diff real.

### Classificação

| Classe | Características | Estratégia |
|--------|------------------|------------|
| **SIMPLE** | poucos arquivos, baixo risco, requisito claro, sem dependência | Agente único, sem subagents |
| **SEQUENTIAL** | uma etapa depende do resultado da anterior (ex.: schema → backend → contrato → frontend → testes) | Agente único ou etapas sequenciais, nunca paralelas |
| **PARALLEL** | subtarefas em arquivos diferentes, contrato já definido, baixo compartilhamento de contexto | Subagents em paralelo, com revisão central depois |
| **COMPLEX** | múltiplos módulos, alto risco, banco + backend + frontend juntos, regra de negócio não trivial | Coordenador central: decompõe, define dependências e responsabilidades, integra, revisa |

Exemplo SIMPLE no Moday: corrigir uma mensagem de validação em `StoreCategoryRequest`. Um agente, direto.

Exemplo SEQUENTIAL no Moday: adicionar um filtro novo em `OrderApiController::index()`. A query no `OrderRepository` precisa existir antes do controller expor o parâmetro, que precisa existir antes do hook do frontend consumir, que precisa existir antes do componente de filtro na UI. Não paralelizar.

Exemplo PARALLEL no Moday: adicionar testes de Feature para 3 módulos independentes já implementados (ex.: `Category`, `Product`, `Table`) — cada um em arquivo de teste próprio, sem dependência entre si. Pode ser 3 agentes em paralelo + 1 revisão central.

Exemplo COMPLEX no Moday: um módulo novo de ponta a ponta (migration + model + repository + service + controller + rotas + hook + páginas + testes) tocando auth/ACL. Coordenador central define o contrato do endpoint primeiro, só então libera trabalho em paralelo nas partes que não dependem umas das outras.

### Contrato de subagent (quando usar mais de um)

Cada subagent deve receber, no mínimo: o objetivo, os arquivos permitidos, os arquivos proibidos (para não colidir com outro agente), as dependências que ele espera já prontas, e o formato de saída esperado. O agente não decide arquitetura global sozinho — decisões que afetam mais de um módulo voltam para quem está coordenando.

---

## 5. Entregar e PARAR (Medium / High)

**Não implementar ainda.** Produzir:

### Goal
Pedido reformulado: objetivos, comportamento esperado, critérios de aceite.

### Complexity Assessment
Low | Medium | High | Critical — com justificativa breve.

High/Critical no Moday: auth, ACL/permissões, migrations destrutivas, dados de produção, qualquer coisa que toque `tenant_id` em múltiplos módulos ao mesmo tempo.

### Agent Strategy
SIMPLE | SEQUENTIAL | PARALLEL | COMPLEX (seção 4) — com justificativa breve.

### Blocking Questions (0–3)
Só se a premissa errada invalidar a solução. Cada pergunta com **default recomendado**.
Se nada bloquear: `Blocking Questions: **0**`

### Assumptions
Numeradas, específicas, testáveis. Cobrir dados, falhas, fronteiras de API, estado/tenant, ambiente, fora de escopo, testes a escrever e a omitir.

### Risk Analysis
Técnico / negócio / migração / performance / segurança — cada um com mitigação.

### Architecture Decision
Escolhida | alternativa | por que rejeitada. Preferir precedente do módulo vizinho.

### Plan
Arquivos criar/alterar; migrations; endpoints; testes; ordem incremental; paths reais do repo.

### Rollback Plan
Migration down / revert de commit. Sem inventar feature flag se o projeto não usa (não usa).

### Validation Checklist
Alinhar a `docs/specs/audit.md` e `testing.md`: testes Feature/Unit, regressão do módulo tocado.

**Parar. Esperar aprovação explícita. Sem código e sem editar arquivos até lá.**

---

## 6. Proporcionalidade

| Nível | Exemplos | Ação |
|-------|----------|------|
| Small | typo, comentário, rename óbvio, ~&lt;20 linhas | Implementar direto, agente único |
| Medium | endpoint no padrão, UI, validação, refactor local | Seção 5 → aprovação → implementar |
| High/Critical | auth, ACL, migrations, delete em massa | Seção 5 + aprovação obrigatória |

Não escale a estratégia de agentes (seção 4) além do que a tarefa realmente pede — trocar o texto de um botão nunca justifica múltiplos agentes.

---

## 7. Durante a implementação

Se assumptions falharem ou a arquitetura divergir:

1. Parar
2. Explicar o que mudou, risco e plano atualizado
3. Não improvisar camadas novas

Preservar:

- Controller fino; Service com regra; Repository só persistência; `ApiResponseClass`
- FE sem regra de negócio no componente; sempre via `use-authenticated-api.ts`, nunca `use-api.ts` legado
- Secrets só em `.env`
- Preferir 404/403 a vazar recurso de outro tenant

---

## 8. Princípios

Reuse → Composition → Simplicidade → Convenção do repo → Mudanças pequenas → Compatibilidade → Testabilidade → Segurança multi-tenant.

SOLID, DRY, KISS, YAGNI — sem abstração "para o futuro".

---

## 9. Proibido

- Duplicar Controller / Service / Repository / Hook / Componente
- Inventar padrão diferente do módulo vizinho (service-facade, Ports/Adapters, exception de domínio custom)
- Bypass de FormRequest / Zod
- Eloquent ou HTML no Controller
- Quebrar contrato de API consumido pelo frontend sem aprovação
- Secrets no código; `--no-verify`; force push em main
- Commit / push / deploy sem pedido explícito do usuário
- Dois agentes editando o mesmo arquivo ao mesmo tempo
- Considerar uma implementação concluída só porque um agente afirmou que terminou
- Dead code ou blocos comentados novos

---

## 10. Definition of Done

Só encerre quando:

1. Critérios de aceite ok
2. Arquitetura do módulo preservada
3. Sem duplicação / abstração desnecessária
4. Tenant / auth / ACL respeitados quando aplicável
5. Validação de input ok
6. Testes alinhados ao domínio (ou justificativa)
7. **`docs/specs/audit.md` percorrido** e desvios bloqueantes corrigidos
8. Docs/specs atualizados se o contrato mudou
9. Graphify atualizado no(s) app(s) tocado(s) (`graphify update .`)
10. Resultado validado objetivamente (testes passando), não só "o agente disse que terminou"

O audit **não é opcional** e **não é substituído** por este protocolo.
