# Testing — práticas atuais

## Backend (`backend_moday/tests`)

| Tipo | Onde | Foco |
|------|------|------|
| Feature | `tests/Feature/{Admin,Api,Auth,Dashboard,Email,Integration,Integrations,Middleware,Notifications,Reports,SalesPerformance}` | HTTP API, middlewares, fluxos |
| Unit | `tests/Unit/{Adapters/Email,Mail,Models,Rules,Services}` | Services, Rules, Adapters de email |

Base: `Tests\TestCase` com `RefreshDatabase`. Helpers reais:
- `authenticatedUser(array $attributes = [])` — cria `User::factory()`, loga via `auth('api')->login($user)`, retorna `['user','token','headers']`
- `actingAsUser($user = null)` — cria/loga usuário e retorna `$this->withHeaders([...Bearer...])`
- **Não existe** `grantFullAccess()`; bypass de permissão é feito ad hoc com `withoutMiddleware([JWTAuthenticate::class, ...])` + `actingAs($user)` em casos pontuais (ex.: `FinancialCategoryApiTest`, `ServiceTypeApiTest`, `SalesPerformanceControllerTest`)
- `setUp()` do `TestCase` fixa `jwt.ttl=60`, `cache.default=array`, `session.driver=array`; `tearDown()` limpa cache

Padrões observados:
- Estilo misto: atributo `#[Test]` (`PHPUnit\Framework\Attributes\Test`) e docblock `/** @test */` coexistem na mesma suíte — seguir o que o arquivo vizinho já usa
- Factories Eloquent (`Plan::factory()`, `Tenant::factory()`, `User::factory()`, etc.)
- `Http::fake()` / `Config::set()` para clients externos (iFood, email)
- `composer.json` tem script `ci:architecture` = `artisan audit:layers` (lint arquitetural customizado) + `test:admin` (`artisan test tests/Feature/Admin/`)

Ao testar endpoint novo no mesmo estilo do módulo:
1. Happy path 200/201
2. Persistência (`assertDatabaseHas`)
3. Validação 422 quando aplicável
4. 404/403 tenant/recurso quando o módulo já cobre

## Frontend (`moday_frontend`)

- Jest (via `next/jest`) + jsdom + Testing Library, `jest.setup.js`, alias `@/`
- 96 arquivos de teste: centralizados em `src/__tests__/` (`app/`, `components/`, `contexts/`, `cruds/` por domínio, `hooks/`, `lib/`, `permissions/`, `pos/`) + colocados em `.../__tests__/` perto da rota/componente
- Harness compartilhado: `src/__tests__/utils/test-utils.tsx` — mocka `@/lib/api-client`, **todos** os hooks de `@/hooks/use-authenticated-api` (retorno default `{ data: [], loading: false, error: null, refetch: jest.fn(), isAuthenticated: true, pagination }`), `@/hooks/use-api` (legado), e expõe geradores (`generateUser`, `generateProduct`, `generateOrder`, `generateClient`, `generateTask`)
- Scripts: `npm test` / config Jest do `package.json`

## O que não exigir sem precedente

- Coverage 100%
- E2E Playwright como gate obrigatório (não identificado no projeto)
- Testar Blade pixel-perfect (só o painel admin interno usa Blade/Inertia/Livewire)

## Checklist pós-implementação

- [ ] Teste Feature/Unit alinhado ao domínio tocado
- [ ] Testes existentes do módulo ainda passam
- [ ] `composer run ci:architecture` sem violação nova, se a mudança for no backend
- [ ] Mocks de API externa (iFood/email) quando necessário
