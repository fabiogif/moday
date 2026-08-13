# Arquitetura do Moday (Alba Tec)

Documento descritivo da arquitetura **atual**. Não introduz padrões novos.

Evidências detalhadas da descoberta: `docs/architecture-current.md`.

## Visão geral

Monorepo com múltiplas superfícies:

| Parte | Path | Stack |
|-------|------|--------|
| Backend API | `backend_moday/` | Laravel 11 (PHP 8.2), JWT (Tymon) + Sanctum (admin), Eloquent, DomPDF, Reverb |
| Frontend tenant | `moday_frontend/` | Next.js App Router, React, TypeScript, shadcn/ui |
| Mobile | `mobile/` e `moday_mobile/` | Dois diretórios de app mobile presentes no repo — confirmar qual é o ativo antes de tratar como referência |

> Marca pública: **Alba Tec** (não "Moday" em telas voltadas ao usuário final).

Fluxo típico autenticado (tenant):

```
Browser / App
  → Next.js (middleware cookie 'auth-token' + AuthGuard)
  → apiClient (Bearer JWT)
  → Laravel routes/api.php
  → Middleware (auth:api, tenant.blocked, trial.check, acl.permission:*, plan.feature:*)
  → Controller
  → Service (ou facade de sub-services em domínios maiores, ex.: Orders)
  → RepositoryInterface → Repository → Model
  → ApiResponseClass / Resource
```

Existem **três guards** de autenticação (`config/auth.php`), não apenas um:
- `api` — JWT, provider `users` (tenant/dashboard)
- `client` — JWT, provider `clients` (loja pública / storefront)
- `admin` — Sanctum, provider `admin_users` (painel admin)

## Camadas (backend)

### Controller (`app/Http/Controllers/`)

- API: maioria `*ApiController` em `Api/` (ex.: `OrderApiController`, `ProductApiController`)
- Admin API: `Api/Admin/*` (ex.: `AdminTenantController`, `AdminPlanController`)
- Admin painel (Blade/Inertia/Livewire): `Admin/*` (ex.: `PermissionController`, `PlanController`)
- Auth: `Auth/*` (estilo Breeze) + `Api/Auth/RegisterApiController`
- Integrações: `Api/Integrations/Ifood/*` (`IfoodAuthController`, `IfoodCatalogController`, `IfoodOAuthController`, `IfoodOrderController`, `IfoodWebhookController`)

Responsabilidade observada nos fluxos maduros:
- Auth tenant (`AuthTenantService::requireAuthenticatedTenant()`)
- Validação (Form Request — padrão dominante, 108 classes em `Http/Requests/`)
- Chamar Service
- Responder via `ApiResponseClass` (a maioria não estende `BaseController`, que existe como wrapper opcional com `successResponse()`/`errorResponse()`/etc.)

### Service (`app/Services/`)

- Predominantemente **flat** (~85 arquivos), sem subpastas por domínio, exceto integrações (`Services/Integrations/Ifood/`)
- Domínios maiores usam **service-facade + sub-services**: `OrderService` delega para `OrderCreationService`, `OrderQueryService`, `OrderWorkflowService`, `OrderLifecycleService` (mais `OrderStatusService`, `OrderIdentifierService`) — não é um único `*Service` monolítico
- Orquestra regras de negócio; injeta `*RepositoryInterface` e outros services (ex.: `CacheService`)

### Repository (`app/Repositories/` + `Contracts/`)

- ~50 pares `FooRepository` / `Contracts/FooRepositoryInterface`, flat
- `BaseRepository` / `BaseRepositoryInterface` como base comum
- `Contracts/PaginateRepositoryInterface` + `Contracts/Presenter/PaginatePresenter` — abstração de paginação própria (não é `LengthAwarePaginator` cru)
- Bind por domínio em `bootstrap/providers.php`, em **9 providers dedicados**: `CoreRepositoryServiceProvider`, `FinancialRepositoryServiceProvider`, `LoyaltyRepositoryServiceProvider`, `IntegrationRepositoryServiceProvider`, `AnalyticsRepositoryServiceProvider`, `NotificationRepositoryServiceProvider`, `MarketingRepositoryServiceProvider`, `PDVRepositoryServiceProvider`, `AdminRepositoryServiceProvider` (`RepositoryServiceProvider` legado ficou vazio, dividido nos anteriores)

### Model (`app/Models/`)

- Eloquent; trait `BelongsToTenant` para escopo de tenant quando aplicável
- SoftDeletes em vários modelos

### Outras pastas relevantes

| Pasta | Uso atual |
|-------|-----------|
| `Http/Requests/` | Form Requests (padrão dominante de validação) |
| `Http/Resources/` | Serialização API (40 classes, inclui `Integrations/Ifood/*`) |
| `Http/Middleware/` | JWT/ACL/trial/plan-feature/CORS/security headers (31 arquivos) |
| `Classes/ApiResponseClass.php` | Envelope JSON padrão |
| `DTO/` | Poucos DTOs — `CreateTenantDTO`/`UpdateTenantDTO` e `Integrations/Ifood/*` |
| `Reports/` | `Builders/`, `Queries/`, `Exporters/` (Pdf/Excel/Csv), `Formatters/`, `Contracts/` |
| `Ports/` + `Adapters/` | Hexagonal parcial — iFood (`Ports/Integrations/Ifood`, `Adapters/Integrations/Ifood/Http`) e Email (`Adapters/Email/*`) |
| `Exceptions/` | **Só `Handler.php`** — não há exceções de domínio customizadas; erros de domínio usam `\DomainException` genérica capturada por controller |
| `Helpers/`, `Rules/`, `Enums/`, `Jobs/`, `Events/`, `Listeners/` | Suporte transversal |

## Camadas (frontend)

| Pasta | Uso |
|-------|-----|
| `src/app/` | App Router: `(dashboard)` (app tenant, pastas **planas** por módulo — não há aninhamento tipo "cardápio/produtos"), `auth/` (login real — `(auth)/` é template legado não usado), `admin/` (painel admin separado), `store/[slug]/` (loja pública), `api/` (Route Handlers usados só pela landing page, não é BFF do dashboard) |
| `src/components/` | `ui/` (shadcn) + componentes cross-cutting (`admin/`, `forms/`, `landing/`, `layouts/`, `location/`, `notifications/`, `subscription/`, `theme-customizer/`) — a maioria dos componentes de domínio fica **colocado** em `.../components/` perto da rota (38 pastas assim) |
| `src/hooks/` | `use-authenticated-api.ts` (ativo, 32 importadores), hooks de domínio, `use-realtime*.ts` (Echo/Reverb) |
| `src/lib/` | `api-client.ts`, `admin-api-client.ts`, `echo.ts`, `api-config.ts`, `auth-routes.ts`, helpers |
| `src/contexts/` | `auth-context.tsx`, `admin-auth-context.tsx`, sidebar/tema/notificações |

> `src/hooks/use-api.ts` é código morto (0 importações fora de testes) mas ainda é mockado em `test-utils.tsx` — candidato a limpeza, não copiar esse padrão em código novo.

## Organização de módulos

Módulos backend se organizam por **feature/domínio** (services + controllers + repos), não por Clean Architecture estrita.

Exemplos:
- **Orders (Pedidos)** — `OrderApiController` → `OrderService` (facade) → `OrderCreationService`/`OrderQueryService`/`OrderWorkflowService`/`OrderLifecycleService` → `OrderRepositoryInterface` → `OrderRepository`
- **Products/Categories (Cardápio)** — `ProductApiController`/`CategoryApiController` → `ProductService`/`CategoryService` → respectivos repositories
- **Financeiro** — `AccountPayableApiController`, `AccountReceivableApiController`, `ExpenseApiController`, `FinancialCategoryApiController`, `BankAccountController`
- **Marketing/Loyalty** — `LoyaltyClientApiController`, `LoyaltyProgramApiController`, `LoyaltyRewardApiController`, `CouponApiController`, `NewsletterApiController`
- **iFood** — `Api/Integrations/Ifood/*` → `Services/Integrations/Ifood/*` → Ports/Adapters → clients externos
- **Reports** — Form Request/filtros → `Builders/*` → `ReportService::generate()` → `Exporters/*` (Pdf/Excel/Csv)

Frontend espelha rotas por feature em `app/(dashboard)/…` (`orders`, `orders/board`, `products`, `financial/*`, `loyalty/*`, `integrations`, etc.), agrupadas visualmente no sidebar (não em pastas aninhadas).

## Autenticação e autorização

- Guards: `api` (JWT, tenant), `client` (JWT, storefront), `admin` (Sanctum, painel admin)
- Tenant: `AuthTenantService::requireAuthenticatedTenant()`
- ACL: middleware `acl.permission:{resource.action}` (ex.: `users.index`) + `hasPermissionTo`
- Plan features: `plan.feature:{key}` (ex.: `plan.feature:reports`) → `PlanFeatureService`
- Limites de plano: `plan.order_limit`, `plan.user_limit`
- Trial / tenant bloqueado: `trial.check`, `tenant.blocked`
- Admin: `admin.auth`, `admin.permission`, `admin.log`

Frontend: JWT em `localStorage['auth-token']` + cookie espelhado (não httpOnly) lido pelo `middleware.ts`; `AuthProvider` (`auth-context.tsx`) + `AuthGuard`. Admin usa fluxo isolado: `admin-api-client.ts` + `admin-token` + `AdminAuthProvider` — o `middleware.ts` do Next **não protege `/admin`**, a proteção é só client-side via `AdminAuthProvider`.

## Tempo real

WebSocket via **Laravel Reverb** (não Pusher Cloud — `BROADCAST_DRIVER=reverb`, `laravel/reverb`). Canais em `routes/channels.php`: `tenant.{id}.orders`, `tenant.{id}.dashboard`, `tenant.{id}.presence`. Eventos `ShouldBroadcast`: `OrderCreated`, `OrderUpdated`, `OrderStatusUpdated`, `DashboardMetricsUpdated`, `RealTimeMessage`.

Frontend: `src/lib/echo.ts` (laravel-echo + pusher-js como transporte, broadcaster `reverb`); único consumidor confirmado é o Kanban de pedidos (`orders/board/page.tsx` via `useRealtimeOrders`).

## Realidade mista (documentar, não "corrigir" aqui)

Nem todo endpoint segue Controller→Service→Repository. Existem controllers com Eloquent/`DB` direto, validação inline (12 arquivos vs 108 Form Requests) e responses manuais. O painel admin interno (`Admin/*`, não `Api/Admin/*`) usa Inertia + Livewire + Blade como superfície adicional, coexistindo com a API JSON. Specs e implementação nova devem **seguir o padrão maduro do domínio vizinho**, não inventar uma terceira variante.
