# Backend — especificações atuais

Baseado exclusivamente no código em `backend/`.

## Stack

- Laravel 11, PHP 8.2
- JWT (`tymon/jwt-auth` ^2.0) — guards `api` (dashboard/tenant) e `client` (storefront)
- Eloquent + migrations
- Laravel Reverb (`laravel/reverb`) para WebSocket/tempo real
- `darkaonline/l5-swagger`, `inertiajs/inertia-laravel`, `livewire/livewire`, `laravel/sanctum`, `laravel/telescope`, `predis/predis` estão no `composer.json` mas **não estão em uso ativo**: Inertia só tem o middleware de boilerplate (`HandleInertiaRequests`, sem `Inertia::render()` em nenhum controller); Livewire só tem 1 componente isolado (`SearchZipcode`); Sanctum não está configurado como guard. Não assumir painel admin, PDF ou integrações externas com base nessas dependências.
- Um único `RepositoryServiceProvider` (não há providers por domínio)

## Fluxo padrão (preferido)

```
Route::…->middleware(['auth:api', 'throttle:*', 'acl.permission:*' quando aplicável])
  → Controller
  → Form Request (padrão dominante) ou validate()
  → Service
  → RepositoryInterface
  → Model
  → ApiResponseClass::sendResponse / sendResponsePaginate
```

Referência real: `CategoryApiController` → `CategoryService` → `CategoryRepositoryInterface` → `CategoryRepository`. Todo domínio segue esse padrão de service único — não há domínio com service-facade + sub-services.

## Controllers

- Preferir controllers finos: auth, validação, chamada ao service, resposta
- Construtor com DI (property promotion)
- Envelope de sucesso: `ApiResponseClass::sendResponse($data, $message, $code)` / `sendResponsePaginate($resourceClass, $paginator, $code)`
- Erros: `validationError`, `rollback`, `throw`, `unauthorized`, `forbidden`
- `app/Http/Controllers/BaseController.php` existe como wrapper opcional (`checkPermission()`), mas a maioria dos controllers chama `ApiResponseClass::*` diretamente sem estendê-lo
- Sem hierarquia de exceptions de domínio — capturar `\Exception` genérica no controller quando necessário

Não colocar HTML inline no controller.

`app/Http/Controllers/Admin/*` (6 classes) existe mas **não tem rota registrada** em `routes/api.php` nem `routes/web.php` — não é um painel funcional, não usar como referência de padrão ativo.

## Services

- 18 classes concretas `*Service`, flat em `app/Services/` (`AuthService`, `CategoryService`, `ClientService`, `DashboardService`, `DashboardMetricsService`, `DetailPlanService`, `EvaluationService`, `ImageCompressionService`, `ListingCacheService`, `OrderService`, `PaymentMethodService`, `PermissionService`, `PlanService`, `ProductService`, `TableService`, `TenantService`, `UserService`, `CacheService`)
- Injetar `*RepositoryInterface` e outros services (`CacheService` é o mais comum)
- Sem Request/Response HTTP

## Repositories

```
app/Repositories/Contracts/FooRepositoryInterface.php
app/Repositories/FooRepository.php
```

14 pares. Base comum: `BaseRepository implements BaseRepositoryInterface`. Paginação usa abstração própria: `Contracts/PaginateRepositoryInterface` + `Contracts/Presenter/PaginatePresenter` (não expor `LengthAwarePaginator` cru para fora do repository).

Bind:

```php
$this->app->bind(FooRepositoryInterface::class, FooRepository::class);
```

Feito em `RepositoryServiceProvider` (`bootstrap/providers.php`) — um único provider, não há divisão por domínio.

## Validação

Form Request é o padrão **dominante** (42 classes em `app/Http/Requests/`). `BaseRequest` compartilhado integra com `ApiResponseClass::validationError` (deve retornar 422 + chave `errors` — se algum request estender `BaseRequest` e não seguir esse formato, é bug, não padrão).

`$request->validate([...])` inline existe em uma minoria de controllers — usar Form Request em features novas.

## Serialização

- `*Resource` em `app/Http/Resources/` (16 classes)
- Alguns módulos podem devolver arrays/models direto via `ApiResponseClass` — seguir o vizinho

## DTOs

Raros e pontuais: só `app/DTO/CreateTenantDTO.php` e `UpdateTenantDTO.php` (readonly, constructor promotion). Prefira arrays validados + models, salvo o domínio já usar DTO (Tenant).

## Exceções

- `app/Exceptions/Handler.php` é o **único arquivo** da pasta — não há exceções de domínio customizadas
- Não inventar uma exception de domínio nova sem necessidade real

## Cache

- `app/Services/CacheService.php`: `remember(key, ttl, callback)` + métodos nomeados por domínio (`getClientStats`, `getProductStats`, `getOrderStats`, `getDashboardMetrics`, `getSalesPerformance`, etc.) e invalidação por domínio (`invalidateClientCache`, `invalidateProductCache`, `invalidateOrderCache`, `invalidateCategoryCache`, `invalidateAllTenantCache`, etc.)
- `ListingCacheService` — cache dedicado para endpoints de listagem
- Invalidar cache no write quando o domínio já usa `CacheService`

## Rotas

- API: `routes/api.php` (~305 linhas, agrupada por domínio)
- `routes/web.php` só tem a rota `/` padrão do Laravel (`view('welcome')`) — não há rotas web reais
- Throttles definidos em `bootstrap/app.php` via `RateLimiter::for()`: `api`, `login`, `register`, `password-reset`, `critical`, `read`. **`throttle:write` é referenciado em rotas mas não tem `RateLimiter::for('write')` definido em nenhum lugar** — inconsistência conhecida, não copiar esse padrão (defina o limiter antes de usar um throttle novo)

## Testes backend

- `tests/Feature/` — `Api/`, `Auth/`, `Integration/`, `Middleware/` + arquivos flat na raiz (`CategoryTest`, `CacheTest`, `PermissionCreationTest`, etc.)
- `tests/Unit/` — `Models/`, `Services/`, `App/Models/`
- `Tests\TestCase` com `RefreshDatabase`; helpers próprios `authenticatedUser()` e `actingAsUser()` (login via guard `api`)
- Estilo misto: atributo `#[Test]` e docblock `/** @test */` coexistem — ambos ocorrem na suíte, nenhum foi eliminado
- Não existe comando `ci:architecture`/`audit:layers` no `composer.json`

Ao adicionar endpoint: preferir Feature test cobrindo status, persistência e autorização quando o domínio já testa assim.
