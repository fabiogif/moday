# Testing — práticas atuais

## Backend (`backend_distribtec/tests`)

| Tipo | Onde | Foco |
|------|------|------|
| Feature | `tests/Feature/` | HTTP API, middlewares, fluxos |
| Unit | `tests/Unit/` | Services, Rules, Barcode, Adapters |

Base: `Tests\TestCase` com `RefreshDatabase`, helpers JWT (`actingAsUser`, `authenticatedUser`, `grantFullAccess`).

Padrões observados:
- PHPUnit attributes `#[Test]`
- Feature de logistics: `withoutMiddleware` (JWT/ACL/plan) + `actingAs($user)` em alguns casos
- Factories Eloquent para Tenant/User/SaleOrder/etc.
- `Http::fake` / `Config::set` para clients externos

Ao testar endpoint novo no mesmo estilo do módulo:
1. Happy path 200/201
2. Persistência (`assertDatabaseHas`)
3. Validação 422 quando aplicável
4. 404 tenant/recurso quando o módulo já cobre

## Frontend (`distribtec_frontend`)

- Jest + jsdom + Testing Library
- Suites em `src/__tests__/` e `__tests__` colocados
- Mock de `@/hooks/use-authenticated-api` e toasts
- Scripts: `npm test` / jest configs do package

## O que não exigir sem precedente

- Coverage 100%
- E2E Playwright como gate obrigatório (salvo o time já usar no módulo)
- Testar Blade pixel-perfect

## Checklist pós-implementação

- [ ] Teste Feature/Unit alinhado ao domínio tocado
- [ ] Testes existentes do módulo ainda passam
- [ ] Mocks de API externa quando necessário
