# Arquitetura atual do Moday/Alba Tec — Descoberta

Documento técnico descritivo. **Não propõe melhorias** (ver auditoria de melhorias separada, se houver).
Todas as afirmações abaixo têm evidência no código-fonte (`backend_moday/`, `moday_frontend/`).

---

## 1. Estrutura de diretórios

### Monorepo

| Path | Evidência |
|------|-----------|
| `backend_moday/` | Laravel 11 API |
| `moday_frontend/` | Next.js App Router |
| `mobile/` e `moday_mobile/` | Dois diretórios de app mobile existem no repo — não verificado qual é o ativo; tratar com cautela até confirmar |

### Backend (`backend_moday/app/`)

| Diretório | Responsabilidade observada |
|-----------|----------------------------|
| `Http/Controllers/Api/` | ~65 `*ApiController` (Orders, Products, Financeiro, Loyalty, Users, Dashboard, …) |
| `Http/Controllers/Api/Admin/` | Painel admin via API (`AdminTenantController`, `AdminPlanController`, …) |
| `Http/Controllers/Admin/` | Painel admin server-rendered (Blade/Inertia/Livewire) |
| `Http/Controllers/Api/Integrations/Ifood/` | Integração iFood |
| `Http/Controllers/Auth/` | Estilo Breeze (sessão) |
| `Http/Middleware/` | 31 arquivos — JWT/ACL/trial/plan/CORS/security headers |
| `Http/Requests/` | 108 Form Requests — validação dominante |
| `Http/Resources/` | 40 API Resources |
| `Services/` | ~85 arquivos, flat; `Services/Integrations/Ifood/` como única subpasta de domínio |
| `Repositories/` + `Contracts/` | ~50 pares Repository/Interface + `BaseRepository`, `PaginateRepositoryInterface` |
| `Providers/` | 9 `*RepositoryServiceProvider` por domínio (ver §4) |
| `Classes/ApiResponseClass.php` | Envelope JSON padrão |
| `Reports/` | `Builders/`, `Queries/`, `Exporters/` (Pdf/Excel/Csv), `Formatters/`, `Contracts/` |
| `Ports/` + `Adapters/` | iFood (`Integrations/Ifood`) e Email (`Adapters/Email`) |
| `DTO/` | `CreateTenantDTO`, `UpdateTenantDTO`, `Integrations/Ifood/*` |
| `Exceptions/` | Só `Handler.php` — sem exceptions de domínio |
| `Helpers/`, `Rules/`, `Jobs/`, `Events/`, `Listeners/` | Suporte transversal |

### Frontend (`moday_frontend/src/`)

| Diretório | Evidência |
|-----------|-----------|
| `app/(dashboard)/` | App tenant, pastas planas por módulo (`orders`, `orders/board`, `products`, `financial/*`, `loyalty/*`, `integrations`, `users`, …) |
| `app/auth/` | Login/cadastro real (`(auth)/` é template legado não usado) |
| `app/admin/` | Painel admin, auth client-side isolada |
| `app/store/[slug]/` | Loja pública |
| `app/api/` | Route Handlers só para a landing page (não é BFF do dashboard) |
| `components/ui/` | shadcn |
| `hooks/use-authenticated-api.ts` | Fetch/mutate autenticados (ativo) |
| `hooks/use-api.ts` | Legado morto (0 importações reais) |
| `hooks/use-realtime.ts` | Echo/Reverb (pedidos) |
| `lib/api-client.ts` | `ApiClient` + `endpoints` |
| `lib/admin-api-client.ts` | Cliente do painel admin |
| `lib/echo.ts` | Cliente Laravel Echo/Reverb |
| `contexts/auth-context.tsx` | JWT tenant |
| `contexts/admin-auth-context.tsx` | Sessão admin |
| `contexts/client-auth-context.tsx` | Sessão cliente loja pública |

---

## 2. Fluxo de requisição (backend)

**Padrão predominante (módulos maduros):**

```
routes/api.php
  → middleware: auth:api, tenant.blocked, trial.check, acl.permission:*, plan.feature:*
  → Controller
  → AuthTenantService::requireAuthenticatedTenant()   [app/Services/AuthTenantService.php]
  → validação (Form Request — dominante)
  → Service (ou facade → sub-services em domínios grandes)
  → RepositoryInterface (bound no container)
  → Model Eloquent
  → ApiResponseClass::sendResponse / sendResponsePaginate
```

**Evidências:**
- Domínio grande com facade: `OrderApiController` → `OrderService` (facade) → `OrderCreationService`/`OrderQueryService`/`OrderWorkflowService`/`OrderLifecycleService` → `OrderRepositoryInterface` → `OrderRepository`
- Domínio simples, service único: `ProductApiController` → `ProductService` → `ProductRepositoryInterface`
- Integração externa via Ports/Adapters: `IfoodOrderController` → `Services/Integrations/Ifood/IfoodOrderService` → `Ports/Integrations/Ifood/IfoodOrderPort` → `Adapters/Integrations/Ifood/Http/IfoodOrderHttpAdapter`
- Report: Form Request/filtros → `Reports/Builders/DailySalesReportBuilder` → `ReportService::generate()` → `Reports/Exporters/PdfExporter`

**Exceções documentadas (existem no código):**
- Controllers com Eloquent/`DB` direto e validação inline em cerca de 12 controllers (vs 108 Form Requests)
- Sem hierarquia de exceptions de domínio — `\DomainException` genérica capturada por controller (`OrderApiController::update()`/`archive()`/`advanceStatus()`)
- PDF sem view Blade — `PdfExporter::generateHtml()` monta HTML por concatenação de string

---

## 3. Autenticação e autorização

- **Três guards** (`config/auth.php`): `api` (JWT, `users`), `client` (JWT, `clients`), `admin` (Sanctum, `admin_users`)
- Tenant: `AuthTenantService::requireAuthenticatedTenant()`
- ACL: middleware `acl.permission:{resource.action}` (ex.: `users.index`) + `hasPermissionTo`
- Plan features/limites: `plan.feature:{key}` (ex.: `plan.feature:reports`), `plan.order_limit`, `plan.user_limit`
- Trial / tenant bloqueado: `trial.check`, `tenant.blocked`
- Admin: `admin.auth`, `admin.permission`, `admin.log`
- Global: `GlobalCorsMiddleware`, `RequestIdMiddleware`, `SecurityHeadersMiddleware`

Frontend: JWT em `localStorage['auth-token']` + cookie espelhado (client-side, lido por `middleware.ts`); loja pública usa guard `client` + `client-auth-token`; admin usa `admin-token` + proteção 100% client-side (o `middleware.ts` do Next não intercepta `/admin`).

---

## 4. Repositórios e providers

9 providers de domínio em `bootstrap/providers.php`, cada um com `register()` fazendo binds `Interface::class → Concrete::class`:

```
CoreRepositoryServiceProvider
FinancialRepositoryServiceProvider
LoyaltyRepositoryServiceProvider
IntegrationRepositoryServiceProvider   (iFood)
AnalyticsRepositoryServiceProvider
NotificationRepositoryServiceProvider
MarketingRepositoryServiceProvider
PDVRepositoryServiceProvider
AdminRepositoryServiceProvider
```

`RepositoryServiceProvider` (legado) está vazio — foi dividido nos providers acima.

---

## 5. Tempo real

- **Laravel Reverb** (`laravel/reverb`), não Pusher Cloud — `BROADCAST_DRIVER=reverb`. `config/broadcasting.php` mantém bloco `pusher` como config alternativa (protocolo compatível), mas Reverb é o driver ativo.
- Canais (`routes/channels.php`): `tenant.{id}.orders` (private), `tenant.{id}.dashboard` (private), `tenant.{id}.presence` (presence)
- Eventos `ShouldBroadcast`: `OrderCreated`, `OrderUpdated`, `OrderStatusUpdated`, `DashboardMetricsUpdated`, `RealTimeMessage`
- Frontend: `src/lib/echo.ts` (laravel-echo + pusher-js como transporte, broadcaster `reverb`); único consumidor confirmado é `orders/board/page.tsx` via `useRealtimeOrders`

---

## 6. Cache

`app/Services/CacheService.php` — `remember(key, ttl, callback)` com fallback se o cache falhar; TTLs nomeados (`order_stats` 900s, `dashboard_metrics` 300s, `sales_performance` 600s, `*_list` para listagens). `ListingCacheService` dedicado a listagens. `CacheInvalidationMiddleware` para invalidação centralizada.

---

## 7. Relatórios / PDF

Pipeline Builder → Query → Service → Exporter, real e completo:
- `Reports/Contracts/{ReportBuilderInterface,ReportExporterInterface}`
- `Reports/Builders/{AbstractReportBuilder,ClientsReportBuilder,DailySalesReportBuilder,MonthlyFinancialReportBuilder,TableOccupancyReportBuilder,TopProductsReportBuilder}`
- `Reports/Queries/*`
- `Reports/Exporters/{PdfExporter,CsvExporter,ExcelExporter}`
- `Reports/Formatters/{CurrencyFormatter,DataFormatter}`

PDF usa DomPDF (`barryvdh/laravel-dompdf`), mas **sem Blade** — `PdfExporter::generateHtml()` concatena HTML em string diretamente.

---

## 8. Testes

**Backend**: `tests/TestCase` (`RefreshDatabase`) com helpers próprios `authenticatedUser()`/`actingAsUser()` (login via guard `api`); sem `grantFullAccess()` — bypass é `withoutMiddleware([...])` ad hoc em 4 arquivos. Estilo misto `#[Test]`/`@test`. `composer run ci:architecture` roda `artisan audit:layers` (lint arquitetural custom) + `test:admin`.

**Frontend**: Jest + jsdom (`next/jest`), 96 arquivos de teste, harness `src/__tests__/utils/test-utils.tsx` mocka `api-client`, todos os hooks de `use-authenticated-api` e o legado `use-api`.

---

## 9. Divergências confirmadas vs. um projeto irmão (DistribTec)

Este backend compartilha a mesma linhagem arquitetural de outro produto SaaS do mesmo time (DistribTec — distribuidoras B2B), mas com estas diferenças reais, já refletidas nas specs:

1. Tempo real é **Reverb**, não Pusher Cloud.
2. **Três guards** de auth (`api`/`client`/`admin`), o terceiro via Sanctum.
3. PDF **sem** views Blade — HTML montado em código.
4. Domínio Orders usa **service-facade + 4 sub-services**, não um `*Service` único.
5. **Sem exceptions de domínio customizadas** — só `\DomainException` genérica.
6. Painel admin interno é **híbrido Inertia + Livewire + Blade**, coexistindo com a API JSON.
7. Frontend tem um hook de API **legado morto** (`use-api.ts`) ainda mockado em testes.
8. Frontend **não tem** BFF/`internal-api` real — as Route Handlers em `app/api/` só atendem a landing page.

## 10. Realidade mista (documentar, não "corrigir" aqui)

Nem todo endpoint segue Controller→Service→Repository. Specs e implementação nova devem seguir o padrão maduro do domínio vizinho, não inventar uma terceira variante.
