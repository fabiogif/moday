# Backend — especificações atuais

Baseado exclusivamente no código em `backend_distribtec/`.

## Stack

- Laravel 11
- JWT (`tymon/jwt-auth`) — guard `api`
- Eloquent + migrations
- DomPDF (`barryvdh/dompdf`) para PDFs
- Providers de repositório por domínio em `bootstrap/providers.php`

## Fluxo padrão (preferido)

```
Route::…->middleware(['auth:api', 'tenant.blocked', 'trial.check', 'acl.permission:…', 'plan.feature:…'])
  → Controller
  → AuthTenantService::requireAuthenticatedTenant()
  → Form Request ou validate()
  → Service
  → RepositoryInterface
  → Model
  → ApiResponseClass::sendResponse / Resource / PDF stream
```

Referência limpa: `StockMovementApiController` → `StockMovementService` → `StockMovementRepositoryInterface`.

## Controllers

- Preferir controllers finos: auth, validação, chamada ao service, resposta
- Construtor com `private readonly` DI
- Envelope de sucesso: `ApiResponseClass::sendResponse($data, $message, $code)`
- Erros: `validationError`, `rollback`, `unauthorized`, `forbidden`, `conflict`
- PDF: controller só chama service (ex.: `ShipmentPdfController` → `ShipmentPdfService`); HTML em Blade (`resources/views/pdfs/…`)

Não colocar HTML inline no controller.

## Services

- Classes concretas `*Service` (sem `*ServiceInterface` na maior parte do projeto)
- Pastas por domínio quando o módulo cresce (`Services/Logistics/`, `Services/Sale/`)
- Injetar `*RepositoryInterface` e outros services
- Sem Request/Response HTTP

## Repositories

```
app/Repositories/Contracts/FooRepositoryInterface.php
app/Repositories/FooRepository.php
```

Bind:

```php
$this->app->bind(FooRepositoryInterface::class, FooRepository::class);
```

Providers observados: `CoreRepositoryServiceProvider`, `DistribtecRepositoryServiceProvider`, `FinancialRepositoryServiceProvider`, `IntegrationRepositoryServiceProvider`, etc.

Métodos típicos: `findForTenant`, `paginateForTenant`, `create`, `update`, persistência de pivots.

## Validação

Dois estilos coexistentes (usar o do módulo vizinho):
1. Form Request em `app/Http/Requests/` (`BaseRequest` → `ApiResponseClass::validationError`)
2. `$request->validate([...])` no controller

## Serialização

- CRUD clássico: `*Resource` em `app/Http/Resources/`
- Módulos Distribtec novos: arrays / models via `ApiResponseClass` (sem Resource obrigatório)

## DTOs

Raros. Existem em `app/DTO/` (Tenant) e `Services/Barcode/DTO/BarcodeProductData.php`.  
Prefira arrays validados + models, salvo o domínio já usar DTO.

## Exceções

- `Handler` mapeia JWT/auth para `ApiResponseClass::unauthorized`
- Domain: `StockException`, `CreditException`, `FiscalException`, `RegulatoryException` → tratados no controller (422)

## Relatórios e PDF

- Pipeline: Builder (`Reports/Builders`) → Query → `ReportService` → Exporter (`PdfExporter`, Excel, Csv)
- PDF tabular: view `resources/views/pdfs/reports/table.blade.php`
- Romaneio: `ShipmentPdfService` + `resources/views/pdfs/shipment/romaneio.blade.php`

## Integrações

Hexagonal parcial:
- `Ports/Integrations/Ifood/*`
- `Adapters/Integrations/Ifood/Http/*`
- Email via `Adapters/Email/*`

## Rotas

- API: `routes/api.php`
- Throttles: `api`, `login`, `critical`, `read`, etc. (definidos no bootstrap)

## Testes backend

- `tests/Feature/` — API e fluxos
- `tests/Unit/` — services, rules, barcode
- `TestCase` com `RefreshDatabase` + helpers JWT (`actingAsUser`, `grantFullAccess`)
- Feature tests de logistics frequentemente usam `withoutMiddleware` + `actingAs`

Ao adicionar endpoint: preferir Feature test cobrindo status, persistência e autorização quando o domínio já testa assim.
