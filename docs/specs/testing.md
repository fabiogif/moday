# Testing — práticas atuais

## Backend (`backend/tests`)

| Tipo | Onde |
|------|------|
| Feature | `tests/Feature/{Api,Auth,Integration,Middleware}` + arquivos flat na raiz (`CategoryTest`, `CacheTest`, `PermissionCreationTest`, `ListingCacheTest`, etc.) |
| Unit | `tests/Unit/{Models,Services,App/Models}` |

Base: `Tests\TestCase` com `RefreshDatabase`. Helpers reais:
- `authenticatedUser(array $attributes = [])`
- `actingAsUser($user = null)`

Padrões observados:
- Estilo misto: atributo `#[Test]` (`PHPUnit\Framework\Attributes\Test`) e docblock `/** @test */` coexistem — seguir o que o arquivo vizinho já usa
- Factories Eloquent (`Category::factory()`, `Tenant::factory()`, `User::factory()`, `Plan::factory()`, etc.)
- **Não existe** `composer run ci:architecture` nem `artisan audit:layers` — não referenciar esse comando

Ao testar endpoint novo no mesmo estilo do módulo:
1. Happy path 200/201
2. Persistência (`assertDatabaseHas`)
3. Validação 422 quando aplicável
4. 404/403 tenant/recurso quando o módulo já cobre

## Frontend (`frontend`)

- Jest (via `next/jest`) + jsdom + Testing Library
- 11 arquivos de teste em `src/__tests__/`: `cruds/` (orders, tasks, permissions, categories, users, clients, products, roles, index) + `permissions/` (2 arquivos), mais `test-utils.tsx` compartilhado
- **Não há** Vitest, nem Playwright/e2e

## O que não exigir sem precedente

- Coverage 100%
- E2E Playwright como gate obrigatório (não existe no projeto)
- `composer run ci:architecture` (não existe)

## Checklist pós-implementação

- [ ] Teste Feature/Unit alinhado ao domínio tocado
- [ ] Testes existentes do módulo ainda passam
