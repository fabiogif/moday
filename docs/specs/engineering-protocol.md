# Protocolo de Engenharia — Moday / Alba Tec

Processo obrigatório para mudanças **Medium** e **High/Critical**.
Complementa (não substitui) `docs/specs/audit.md`: este arquivo cobre o **antes** (investigar → plano → aprovação); o audit cobre o **depois** (checklist de fechamento).

Prioridade se houver conflito: `moday.md` → `laravel.md` / `react.md` → `graphify.md` → este protocolo.

Não existe `AGENTS.md` neste projeto. Não invente Clean Architecture / DDD / UseCases sem precedente no módulo.

---

## 0. Fontes de verdade

| Prioridade | Fonte |
|------------|--------|
| 1 | `docs/specs/moday.md` + `.cursor/rules/moday.mdc` |
| 2 | `docs/specs/laravel.md` / `react.md` (+ rules) |
| 3 | `docs/specs/graphify.md` + `.cursor/rules/graphify.mdc` |
| 4 | Demais `docs/specs/*` e `.cursor/rules/*` |
| 5 | `CLAUDE.md` |
| 6 | Código do **módulo vizinho** maduro |

Se documentação e implementação divergirem: reporte a inconsistência e siga o padrão predominante do módulo (não a exceção).

---

## 1. Investigar antes de perguntar

Nunca pergunte o que o repositório já responde.

### Graphify primeiro

Antes de exploração ampla (`Read` / `Grep` / `Glob`):

```bash
graphify query "<pergunta>"
graphify path "<A>" "<B>"
graphify explain "<conceito>"
```

O `graphify-out/` da raiz é **merge** de `backend_moday`, `moday_frontend` e `moday_mobile`. Nunca `graphify update .` na raiz.

Após alterar código nos apps tocados:

```bash
graphify update ./backend_moday
graphify update ./moday_frontend
graphify update ./moday_mobile
graphify merge-graphs ./backend_moday/graphify-out/graph.json ./moday_frontend/graphify-out/graph.json ./moday_mobile/graphify-out/graph.json --out graphify-out/graph.json
```

(Atualize só os apps modificados; sempre rode `merge-graphs` em seguida.)

### Também consultar

Specs, rules, manifests/lockfiles, `.env.example`, testes, CI/Docker/scripts de deploy, implementações similares no módulo vizinho.

### Não perguntar (já definido)

- Stack: Laravel + Next.js/React + mobile separado
- BE: Routes → Controller → FormRequest → Service → Repository → Model → Resource
- FE: Page → Hook → `apiClient`/`endpoints` → componente reutilizável
- Multi-tenant: `tenant_id` / `AuthTenantService` / `forTenant`
- Auth: `auth:api` + `acl.permission` + `plan.feature` / `plan.order_limit` / `plan.user_limit` quando o módulo usa
- Validação: Form Request (BE) / Zod + RHF (FE)
- Design System Alba Tec (violeta `#7C3AED`, Poppins)
- Naming e pastas: `docs/specs/naming.md` + módulo vizinho

---

## 2. Análise de arquitetura

Antes de propor solução, identificar no módulo alvo:

- Fluxo Controller → Service → Repository (facade + sub-services só se o domínio já tiver, ex. Orders)
- Hooks / `endpoints` / componentes reutilizáveis no FE
- Middleware auth, tenant, ACL, plan
- Abstrações existentes — **estender, não duplicar**

Nunca criar sem precedente: Entities DDD, UseCase classes, ServiceInterface, DTOs/libs “genéricas”.

---

## 3. Análise de impacto

| Área | Checar |
|------|--------|
| Direto | Arquivos nos apps corretos |
| Indireto | Dependências via graphify |
| Compatibilidade | APIs públicas/tenant; mobile se consumir o contrato |
| Banco | Migrations, índices, queries, seeders — sem DROP/TRUNCATE destrutivo sem consentimento explícito |
| FE | Pages, hooks, state, cache cliente |
| BE | Controllers, Services, Repos, Jobs, Events, Permissions, plan features |
| Infra | Docker, Redis/cache, workers, storage, deploy Oracle |

Se afetar **auth, ACL, schema, APIs públicas ou vários módulos**: parar após a análise, explicar riscos e aguardar aprovação.

---

## 4. Entregar e PARAR (Medium / High)

**Não implementar ainda.** Produzir:

### Goal
Pedido reformulado: objetivos, comportamento esperado, critérios de aceite.

### Complexity Assessment
Low | Medium | High | Critical — com justificativa breve.

High/Critical no Moday: auth, permissões, pagamentos, migrations destrutivas, multi-tenant amplo, filas/cache de listagem, sync, dados de produção.

### Blocking Questions (0–3)
Só se a premissa errada invalidar a solução. Cada pergunta com **default recomendado**.  
Se nada bloquear: `Blocking Questions: **0**`

### Assumptions
Numeradas, específicas, testáveis. Cobrir dados, falhas, fronteiras de API, estado/tenant, ambiente, fora de escopo, testes a escrever e a omitir.

### Risk Analysis
Técnico / negócio / migração / performance / segurança — cada um com mitigação.

### Architecture Decision
Escolhida | alternativa | por que rejeitada. Preferir precedente do módulo.

### Plan
Arquivos criar/alterar; migrations; endpoints; testes; ordem incremental; paths reais do monorepo.

### Rollback Plan
Migration down / revert de deploy / compatibilidade de API. Sem inventar feature flag se o projeto não usa.

### Validation Checklist
Alinhar a `docs/specs/audit.md` e `testing.md`: testes, regressão do módulo, `composer run ci:architecture` se BE, smoke se houver deploy.

**Parar. Esperar aprovação explícita. Sem código e sem editar arquivos até lá.**

---

## 5. Proporcionalidade

| Nível | Exemplos | Ação |
|-------|----------|------|
| Small | typo, comentário, rename óbvio, ~&lt;20 linhas | Implementar direto |
| Medium | endpoint no padrão, UI, validação, refactor local | Seção 4 → aprovação → implementar |
| High/Critical | auth, ACL, plan, payments, migrations, infra, delete em massa, sync | Seção 4 + aprovação obrigatória |

---

## 6. Durante a implementação

Se assumptions falharem ou a arquitetura divergir:

1. Parar  
2. Explicar o que mudou, risco e plano atualizado  
3. Não improvisar camadas novas  

Preservar:

- Controller fino; Service com regra; Repository só persistência; `ApiResponseClass`
- FE sem regra de negócio no componente; paths em `endpoints`; nunca `use-api.ts` legado
- Secrets só em `.env`
- Preferir 404 a vazar recurso de outro tenant

---

## 7. Princípios

Reuse → Composition → Simplicidade → Convenção do repo → Mudanças pequenas → Compatibilidade → Testabilidade → Segurança multi-tenant.

SOLID, DRY, KISS, YAGNI — sem abstração “para o futuro”.

---

## 8. Proibido

- Duplicar Controller / Service / Repository / Hook / Componente / Modal
- Inventar padrão diferente do módulo vizinho
- Bypass de FormRequest / Zod
- Eloquent ou HTML no Controller (salvo exceção já existente no módulo)
- Quebrar API pública sem aprovação
- Secrets no código; `--no-verify`; force push em main/master
- Commit / push / deploy sem pedido explícito do usuário
- `graphify update .` na raiz
- Dead code ou blocos comentados novos

---

## 9. Definition of Done

Só encerre quando:

1. Critérios de aceite ok  
2. Arquitetura do módulo preservada  
3. Sem duplicação / abstração desnecessária  
4. Tenant / auth / ACL / plan respeitados quando aplicável  
5. Validação de input ok  
6. Testes alinhados ao domínio (ou justificativa)  
7. **`docs/specs/audit.md` percorrido** e desvios bloqueantes corrigidos  
8. Docs/specs atualizados se o contrato mudou  
9. Graphify atualizado nos apps tocados + `merge-graphs`  
10. Design System respeitado no FE  

O audit **não é opcional** e **não é substituído** por este protocolo.
