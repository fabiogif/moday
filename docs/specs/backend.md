# Backend — especificações atuais

Baseado exclusivamente no código em `backend_moday/`.

## Stack

- Laravel 11, PHP 8.2
- JWT (`tymon/jwt-auth` ^2.0) — guards `api` (tenant) e `client` (storefront); guard `admin` usa **Sanctum** (`laravel/sanctum`)
- Eloquent + migrations
- DomPDF (`barryvdh/laravel-dompdf`) para PDFs — **sem views Blade**: o HTML é montado por concatenação de string em `Reports/Exporters/PdfExporter::generateHtml()`
- Laravel Reverb (`laravel/reverb`) para WebSocket/tempo real (protocolo compatível com Pusher, mas não é Pusher Cloud)
- `darkaonline/l5-swagger` — documentação OpenAPI inline (`@OA\...`) sobre as rotas
- `inertiajs/inertia-laravel` + `livewire/livewire` — usados só no painel admin interno (`Admin/*`, Blade), não na API JSON
- Providers de repositório por domínio em `bootstrap/providers.php`

## Fluxo padrão (preferido)

```
Route::…->middleware(['auth:api', 'tenant.blocked', 'trial.check', 'acl.permission:…', 'plan.feature:…'])
  → Controller
  → AuthTenantService::requireAuthenticatedTenant()
  → Form Request (padrão dominante) ou validate()
  → Service (ou facade → sub-services em domínios maiores)
  → RepositoryInterface
  → Model
  → ApiResponseClass::sendResponse / sendResponsePaginate
```

Referência real (domínio maior, com sub-services): `OrderApiController` → `OrderService` (facade) → `OrderCreationService` / `OrderQueryService` / `OrderWorkflowService` / `OrderLifecycleService` → `OrderRepositoryInterface` → `OrderRepository`.

Referência real (domínio simples, service único): `ProductApiController` → `ProductService` → `ProductRepositoryInterface` → `ProductRepository`.

## Controllers

- Preferir controllers finos: auth, validação, chamada ao service, resposta
- Construtor com DI (property promotion); múltiplas dependências injetadas quando o controller cobre vários casos de uso (ex.: `OrderApiController` injeta `OrderService`, `ProductRecommendationService`, `OrderEmailService`)
- Envelope de sucesso: `ApiResponseClass::sendResponse($data, $message, $code)` / `sendResponsePaginate($resourceClass, $paginator, $code)`
- Erros: `validationError`, `rollback`, `throw`, `unauthorized`, `forbidden`, `conflict`
- `app/Http/Controllers/BaseController.php` existe como wrapper opcional (`successResponse`/`errorResponse`/`unauthorizedResponse`/`notFoundResponse`/`validationResponse`), mas a maioria dos controllers chama `ApiResponseClass::*` diretamente sem estendê-lo — seguir o que o controller vizinho já faz
- Erros de domínio: sem hierarquia de exceptions customizada — capturar `\DomainException` (ou `\Exception` genérico) e mapear para `ApiResponseClass::forbidden()`/`rollback()` no próprio controller, como em `OrderApiController::update()`/`archive()`/`advanceStatus()`

Não colocar HTML inline no controller.

## Services

- Classes concretas `*Service` (sem `*ServiceInterface`), predominantemente **flat** em `app/Services/`
- Domínios grandes (ex.: Orders) dividem responsabilidade em vários services especializados injetados por um service-facade — seguir esse padrão só quando o domínio já justificar a divisão; não fragmentar services simples por precaução
- Integrações organizadas em subpasta própria: `Services/Integrations/Ifood/*`
- Injetar `*RepositoryInterface` e outros services (`CacheService`, etc.)
- Sem Request/Response HTTP

## Repositories

```
app/Repositories/Contracts/FooRepositoryInterface.php
app/Repositories/FooRepository.php
```

Base comum: `BaseRepository implements BaseRepositoryInterface`. Paginação usa abstração própria: `Contracts/PaginateRepositoryInterface` + `Contracts/Presenter/PaginatePresenter` (não expor `LengthAwarePaginator` cru para fora do repository quando o domínio já usa esse contrato).

Bind:

```php
$this->app->bind(FooRepositoryInterface::class, FooRepository::class);
```

Providers reais (`bootstrap/providers.php`), um por domínio: `CoreRepositoryServiceProvider`, `FinancialRepositoryServiceProvider`, `LoyaltyRepositoryServiceProvider`, `IntegrationRepositoryServiceProvider`, `AnalyticsRepositoryServiceProvider`, `NotificationRepositoryServiceProvider`, `MarketingRepositoryServiceProvider`, `PDVRepositoryServiceProvider`, `AdminRepositoryServiceProvider` (`RepositoryServiceProvider` legado está vazio — não adicionar binds novos nele).

Métodos típicos: `paginateByTenant`, `createNewOrder`/`create`, `update`, `getByIdentify`/`findForTenant`, persistência de pivots.

## Validação

Form Request é o padrão **dominante** (108 classes em `app/Http/Requests/{Api,Api/Admin,Auth}`, ex.: `StoreOrderRequest`, `UpdateOrderRequest`). `BaseRequest` compartilhado integra com `ApiResponseClass::validationError`.

`$request->validate([...])` inline existe mas é minoritário (~12 controllers) — usar Form Request em features novas, salvo o módulo vizinho já ser 100% inline.

## Serialização

- CRUD clássico: `*Resource` em `app/Http/Resources/` (40 classes, inclui `Integrations/Ifood/*`)
- Alguns módulos podem devolver arrays/models direto via `ApiResponseClass` — seguir o vizinho

## DTOs

Raros e pontuais. Existem em `app/DTO/`: `CreateTenantDTO`, `UpdateTenantDTO` e `Integrations/Ifood/{IfoodOrderDTO,IfoodOrderItemDTO,IfoodOrderStatusDTO}` (readonly, constructor promotion). Prefira arrays validados + models, salvo o domínio já usar DTO (Tenant, iFood).

## Exceções

- `app/Exceptions/Handler.php` é o **único arquivo** da pasta — não há exceções de domínio customizadas (`OrderNotFoundException` etc. não existem)
- `Handler` mapeia exceções do JWT (`TokenExpiredException`, `TokenInvalidException`/`JWTException`, `UnauthorizedHttpException`) e `AuthenticationException` para `ApiResponseClass::unauthorized(...)`
- Erros de domínio: `\DomainException` genérica lançada no Service, capturada no Controller → `ApiResponseClass::forbidden()`/`rollback()`
- Não inventar uma exception de domínio nova sem necessidade real — o padrão observado é `\DomainException` + try/catch no controller

## Relatórios e PDF

- Pipeline real: `Reports/Contracts/{ReportBuilderInterface,ReportExporterInterface}` → `Reports/Builders/*` (ex.: `DailySalesReportBuilder`, `TopProductsReportBuilder`) → `Reports/Queries/*` → `ReportService::generate($builder, $exporter, $filters)` → `Reports/Exporters/*` (`PdfExporter`, `ExcelExporter`, `CsvExporter`)
- `Reports/Formatters/{CurrencyFormatter,DataFormatter}` para formatação de saída
- PDF: `PdfExporter` usa `Barryvdh\DomPDF\Facade\Pdf::loadHTML($html)` com HTML montado por concatenação — **não existe** view Blade para PDF neste projeto; não introduzir `resources/views/pdfs/` sem necessidade real, seguir o padrão de montagem de HTML já usado

## Integrações

Hexagonal parcial:
- `Ports/Integrations/Ifood/{IfoodAuthPort,IfoodCatalogPort,IfoodOrderPort}` — interfaces
- `Adapters/Integrations/Ifood/Http/{IfoodAuthHttpAdapter,IfoodCatalogHttpAdapter,IfoodOrderHttpAdapter}` — implementação via `Http` facade
- `Services/Integrations/Ifood/{IfoodCatalogService,IfoodEventService,IfoodOAuthService,IfoodOrderService,IfoodTokenService}` orquestram os ports
- Repositórios dedicados (`IfoodTokenRepository`, `IfoodOrderRepository`, `IfoodApiLogRepository`, `IfoodOauthSessionRepository`, `IfoodCatalogSnapshotRepository`, `IfoodEventRepository`), bind em `IntegrationRepositoryServiceProvider`
- Email via `Adapters/Email/*` (`Contracts/EmailAdapterInterface` + `EmailAdapterFactory` + `MailchimpAdapter`/`PostmarkAdapter`/`SesAdapter`/`SmtpAdapter`)

## Tempo real (WebSocket)

- `laravel/reverb`, driver `BROADCAST_DRIVER=reverb`
- Canais em `routes/channels.php`: `tenant.{tenantId}.orders` (private), `tenant.{tenantId}.dashboard` (private), `tenant.{tenantId}.presence` (presence)
- Eventos `ShouldBroadcast` em `app/Events/`: `OrderCreated`, `OrderUpdated`, `OrderStatusUpdated`, `DashboardMetricsUpdated`, `RealTimeMessage`

## Cache

- `app/Services/CacheService.php`: `remember(key, ttl, callback)` com fallback silencioso se o cache falhar; TTLs nomeados via const `CACHE_TTL` (ex.: `order_stats` 900s, `dashboard_metrics` 300s, `sales_performance` 600s, `*_list` para listagens)
- `ListingCacheService` — cache dedicado para endpoints de listagem
- `CacheInvalidationMiddleware` — invalidação centralizada
- Invalidar cache no write quando o domínio já usa `CacheService` (ex.: `OrderCreationService` injeta `CacheService`)

## Rotas

- API: `routes/api.php` (~800 linhas, agrupada por domínio, anotações `@OA\...` inline para Swagger)
- Throttles reais: `api`, `login`, `register`, `password-reset`, `critical`, `read`, `write`, `events` — a maioria definida em `RateLimiter::for()` no `bootstrap/app.php`; `write` é definido em `AppServiceProvider`/`RouteServiceProvider`

## Testes backend

- `tests/Feature/` — `Admin/`, `Api/`, `Auth/`, `Dashboard/`, `Email/`, `Integration/`, `Integrations/`, `Middleware/`, `Notifications/`, `Reports/`, `SalesPerformance/`
- `tests/Unit/` — `Adapters/Email/`, `Mail/`, `Models/`, `Rules/`, `Services/`
- `Tests\TestCase` com `RefreshDatabase`; helpers próprios `authenticatedUser()` e `actingAsUser()` (login via guard `api`) — **não existe** `grantFullAccess()`; bypass de middleware é feito ad hoc com `withoutMiddleware([...])` quando o teste precisa (ex.: `FinancialCategoryApiTest`)
- Estilo misto: atributo `#[Test]` e docblock `/** @test */` coexistem na mesma suíte
- `composer.json` define `ci:architecture` (`artisan audit:layers` + `test:admin`) — existe um comando artisan próprio de lint arquitetural (`audit:layers`), útil para checar violações de camada antes de abrir PR

Ao adicionar endpoint: preferir Feature test cobrindo status, persistência e autorização quando o domínio já testa assim.
