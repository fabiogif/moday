# Arquitetura do Moday

Documento descritivo da arquitetura **atual**, verificada diretamente no código. Não introduz padrões novos.

## Visão geral

Monorepo com duas superfícies de aplicação:

| Parte | Path | Stack |
|-------|------|--------|
| Backend API | `backend/` | Laravel 11 (PHP 8.2), JWT (`tymon/jwt-auth`), Eloquent |
| Frontend | `frontend/` | Next.js 15 (App Router), React 19, TypeScript, shadcn/ui |

Não há app mobile neste repositório. `docs/` guarda documentação histórica organizada por tema; `docs/specs/` é a única pasta normativa (fonte de verdade para arquitetura).

Fluxo típico autenticado:

```
Browser
  → Next.js (middleware.ts lê cookie 'auth-token')
  → api-client.ts (Bearer JWT)
  → Laravel routes/api.php
  → Middleware (auth:api, throttle:*, acl.permission:* quando aplicável)
  → Controller
  → Service
  → RepositoryInterface → Repository → Model
  → ApiResponseClass
```

Guards de autenticação reais (`config/auth.php`): apenas **`web`** (sessão, não usado pela API), **`api`** (JWT, provider `users`) e **`client`** (JWT, provider `clients`, storefront público). Não existe guard `admin` nem Sanctum ativo como guard de autenticação — `laravel/sanctum` está no `composer.json` mas não está configurado como guard.

## Camadas (backend)

### Controller (`app/Http/Controllers/`)

- `Api/` — 24 controllers `*ApiController` (ex.: `OrderApiController`, `CategoryApiController`, `ProductApiController`)
- `Admin/` — 6 controllers (`PermissionController`, `ProfileController`, `PlanController`, `PlanProfileController`, `PermissionProfileController`, `DetailPlanController`). **Não têm nenhuma rota registrada** em `routes/api.php` nem `routes/web.php` — código presente mas não exposto/ativo; não tratar como painel admin funcional.
- `Auth/` — fluxo de autenticação da API

Responsabilidade observada: validação via Form Request → chamar Service → responder via `ApiResponseClass`. `app/Http/Controllers/BaseController.php` existe como wrapper opcional (`checkPermission()` e afins), mas a maioria dos controllers chama `ApiResponseClass::*` diretamente sem estendê-lo.

### Service (`app/Services/`)

18 classes, flat, sem subpastas por domínio (`AuthService`, `CategoryService`, `ClientService`, `DashboardService`, `DashboardMetricsService`, `DetailPlanService`, `EvaluationService`, `ImageCompressionService`, `ListingCacheService`, `OrderService`, `PaymentMethodService`, `PermissionService`, `PlanService`, `ProductService`, `TableService`, `TenantService`, `UserService`, `CacheService`). Nenhum domínio usa o padrão service-facade + sub-services — cada domínio tem um `*Service` único.

### Repository (`app/Repositories/` + `Contracts/`)

14 pares `FooRepository` / `Contracts/FooRepositoryInterface`, flat. `BaseRepository implements BaseRepositoryInterface` como base comum. `Contracts/PaginateRepositoryInterface` + `Contracts/Presenter/PaginatePresenter` — abstração de paginação própria (não expõe `LengthAwarePaginator` cru). Bind em um único `RepositoryServiceProvider` (`bootstrap/providers.php`), não há providers por domínio.

### Model (`app/Models/`)

15 models Eloquent: `Category`, `Client`, `DetailPlan`, `Order`, `OrderEvaluation`, `OrderProduct`, `PaymentMethod`, `Permission`, `Plan`, `Product`, `Profile`, `Role`, `Table`, `Tenant`, `User`. Não existe trait `BelongsToTenant` — escopo de tenant é feito manualmente (`where('tenant_id', ...)`) em cada repository/query.

### Outras pastas relevantes

| Pasta | Uso atual |
|-------|-----------|
| `Http/Requests/` | Form Requests (42 classes) — padrão dominante de validação |
| `Http/Resources/` | Serialização API (16 classes) |
| `Http/Middleware/` | 21 arquivos — JWT, `acl.permission` (`PermissionMiddleware`), `csrf.api`, CORS |
| `Classes/ApiResponseClass.php` | Envelope JSON padrão (`sendResponse`, `sendResponsePaginate`, `rollback`, `throw`, `unauthorized`, `forbidden`, `validationError`) |
| `DTO/` | Só 2 DTOs: `CreateTenantDTO`, `UpdateTenantDTO` |
| `Exceptions/` | **Só `Handler.php`** — não há exceções de domínio customizadas |
| `Helpers/`, `Rules/`, `Observers/` | Suporte transversal |

Não existem: `app/Reports/`, `app/Ports/`, `app/Adapters/`, integração iFood, módulos de Financeiro/Loyalty/Marketing, comando `ci:architecture`/`audit:layers`.

## Camadas (frontend)

| Pasta | Uso |
|-------|-----|
| `src/app/` | App Router: `(dashboard)` (~28 segmentos de rota — orders, products, categories, clients, users, permissions, profiles, tables, tasks, calendar, chat, mail, faqs, pricing, reports, payment-methods, settings...), `(auth)` (login/registro — contém variantes duplicadas de template não consolidadas: `sign-in`, `sign-in-2`, `sign-up`, `sign-up-2`, `sign-up-3`, etc.), `landing/`, `store/[slug]/` (loja pública com auth própria de cliente), `api/` (Route Handlers usados só pela landing page) |
| `src/components/` | `ui/` (44 componentes shadcn) + `forms/`, `layouts/`, `landing/`, `theme-customizer/` — a maioria dos componentes de domínio fica **colocada** em `app/**/components/` perto da rota (35 pastas assim) |
| `src/hooks/` | `use-authenticated-api.ts` (dominante, ~30 importadores) — ver nota de depreciação em `use-api.ts` abaixo; `use-realtime.ts`/`use-realtime-dashboard.ts` (Echo/Reverb, uso real confirmado) |
| `src/lib/` | `api-client.ts` (classe `ApiClient` singleton, JWT Bearer), `echo.ts` (Laravel Echo + Pusher-js, broadcaster `reverb`) |
| `src/contexts/` | `auth-context.tsx` (staff/dashboard), `client-auth-context.tsx` (loja pública, separado) |

Não existe rota/painel `admin/` no frontend. Não existe app mobile.

> `src/hooks/use-api.ts` (+ `use-api.ts.backup`) é legado em migração — só 2 usos reais restantes (um deles já comentado como temporário) além de mocks de teste. Não copiar esse padrão em código novo; usar `use-authenticated-api.ts`.

## Organização de módulos

Módulos backend se organizam por **feature/domínio** (service único + repository único), não por Clean Architecture nem service-facade.

Exemplos:
- **Orders** — `OrderApiController` → `OrderService` → `OrderRepositoryInterface` → `OrderRepository`
- **Products/Categories** — `ProductApiController`/`CategoryApiController` → `ProductService`/`CategoryService` → respectivos repositories
- **Dashboard** — `DashboardApiController`/`DashboardMetricsController` → `DashboardService`/`DashboardMetricsService` → `DashboardRepository`, com cache via `CacheService`

Frontend espelha rotas por feature em `app/(dashboard)/…`, agrupadas visualmente no sidebar (não em pastas aninhadas).

## Autenticação e autorização

- Guards: `api` (JWT, dashboard/tenant), `client` (JWT, storefront)
- ACL: middleware `acl.permission` (`PermissionMiddleware`) + `User::hasPermission()`
- Não existem: guard `admin`, `plan.feature`/`plan.order_limit`/`plan.user_limit`, `trial.check`, `tenant.blocked`

Frontend: JWT em `localStorage['auth-token']` + cookie espelhado, lido por `src/middleware.ts` (listas hardcoded de rotas públicas/protegidas). `AuthProvider` (`auth-context.tsx`) para staff; `ClientAuthProvider` (`client-auth-context.tsx`) separado, só para as páginas `store/[slug]/*`. Único componente de guarda de UI: `src/components/PermissionGuard.tsx`.

## Tempo real

WebSocket via **Laravel Reverb** (`BROADCAST_DRIVER=reverb`, não Pusher Cloud). Eventos `ShouldBroadcast` confirmados: `OrderCreated`, `OrderUpdated`, `OrderStatusUpdated`, `DashboardMetricsUpdated`, `RealTimeMessage`.

Frontend: `src/lib/echo.ts` + `use-realtime.ts`/`use-realtime-dashboard.ts` — uso real confirmado em `orders/board/page.tsx` (`useRealtimeOrders`) e `dashboard/components/metrics-overview.tsx`.

## Realidade mista (documentar, não "corrigir" aqui)

Nem todo endpoint segue Controller→Service→Repository sem exceções pontuais (algum uso direto de Eloquent em controller existe, mas Form Request é o padrão dominante — só ~12 controllers ainda usam `validate()` inline). O painel `Admin/*` existe como código mas está desconectado de rotas — não referenciar como se fosse ativo. Specs e implementação nova devem **seguir o padrão maduro do domínio vizinho** (ex.: `Order`/`Category`/`Product`), não inventar uma variante nova.
