# Arquitetura do DistribTec

Documento descritivo da arquitetura **atual**. Não introduz padrões novos.

Evidências detalhadas da descoberta: `docs/architecture-current.md`.

## Visão geral

Monorepo com três superfícies principais:

| Parte | Path | Stack |
|-------|------|--------|
| Backend API | `backend_distribtec/` | Laravel 11, JWT (Tymon), Eloquent, DomPDF |
| Frontend tenant | `distribtec_frontend/` | Next.js App Router, React, TypeScript, shadcn/ui |
| Mobile | `distribtec_mobile/` | App separado (fora do escopo diário da API web) |

Fluxo típico autenticado:

```
Browser / App
  → Next.js (middleware cookie + AuthGuard)
  → apiClient (Bearer JWT)
  → Laravel routes/api.php
  → Middleware (auth:api, tenant.blocked, trial.check, acl.permission, plan.feature)
  → Controller
  → Service
  → RepositoryInterface → Repository → Model
  → ApiResponseClass / Resource / PDF stream
```

## Camadas (backend)

### Controller (`app/Http/Controllers/`)

- API: maioria `*ApiController` em `Api/`; também `*Controller` (ex.: `DeliveryRouteController`)
- Admin: `Admin/`, `Api/Admin/`
- Auth: `Auth/`, `Api/Auth/`
- Integrações: `Api/Integrations/Ifood/`

Responsabilidade observada nos fluxos maduros:
- Auth tenant (`AuthTenantService::requireAuthenticatedTenant()`)
- Validação (Form Request ou `$request->validate()`)
- Chamar Service
- Responder via `ApiResponseClass` ou Resource

### Service (`app/Services/`)

- Flat + subpastas de domínio: `Sale/`, `Logistics/`, `Stock/`, `Barcode/`, `Fiscal/`, `Commercial/`, `Purchase/`, `Integrations/`, `SalesGoal/`, `Seasonal/`, `Audit/`, `Regulatory/`
- Orquestra regras de negócio e outros services
- Injeta `*RepositoryInterface` quando o domínio já tem repository

### Repository (`app/Repositories/` + `Contracts/`)

- Interface em `Contracts/FooRepositoryInterface`
- Implementação `FooRepository`
- Bind em `*RepositoryServiceProvider` por domínio (`DistribtecRepositoryServiceProvider`, `CoreRepositoryServiceProvider`, etc.)

### Model (`app/Models/`)

- Eloquent; scopes de tenant (`forTenant`) quando aplicável
- SoftDeletes em vários modelos

### Outras pastas relevantes

| Pasta | Uso atual |
|-------|-----------|
| `Http/Requests/` | Form Requests |
| `Http/Resources/` | Serialização API (CRUD clássicos) |
| `Http/Middleware/` | JWT/ACL/trial/plan features |
| `Classes/ApiResponseClass.php` | Envelope JSON padrão |
| `DTO/` | Poucos DTOs (Tenant); barcode em `Services/Barcode/DTO/` |
| `Reports/` | Builders, Queries, Exporters, Contracts |
| `Ports/` + `Adapters/` | Hexagonal parcial (Email) |
| `Exceptions/` | Handler + domain exceptions |
| `Helpers/`, `Rules/`, `Enums/`, `Jobs/`, `Events/` | Suporte transversal |

## Camadas (frontend)

| Pasta | Uso |
|-------|-----|
| `src/app/` | App Router: `(dashboard)`, `(auth)`, `admin`, `store`, `delivery`, `api`, `internal-api` |
| `src/components/` | `ui/` (shadcn) + features |
| `src/hooks/` | `useAuthenticatedApi`, `useMutation`, hooks de domínio |
| `src/lib/` | `api-client`, endpoints, helpers |
| `src/contexts/` | Auth, sidebar, notificações |
| `src/types/` | Tipos compartilhados (parcial; muitos tipos ficam nas pages) |

## Organização de módulos

Módulos backend se organizam por **feature/domínio** (services + controllers + repos), não por Clean Architecture estrita.

Exemplos:
- **Sale** — `SaleOrderApiController` → `SaleOrderService` → `SaleOrderRepositoryInterface`
- **Logistics** — `DeliveryRouteController` / `ShipmentPdfController` → `DeliveryRouteService` / `ShipmentPdfService` → `ShipmentRepositoryInterface`
- **Stock** — `StockMovementApiController` → `StockMovementService` → `StockMovementRepositoryInterface`
- **Barcode** — `ProductBarcodeLookupController` → `BarcodeLookupService` + clients externos
- **Reports** — Form Request → Builder → `ReportService` → Exporter (Pdf/Excel/Csv)

Frontend espelha rotas por feature em `app/(dashboard)/…` (ex.: `logistics/shipments`, `products`).

## Autenticação e autorização

- Guard API: JWT (`auth:api`)
- Tenant: `AuthTenantService`
- ACL: middleware `acl.permission:{permission}` + `UserACLTrait`
- Plan features: `plan.feature:{key}` → `PlanFeatureService`
- Trial / tenant bloqueado: `trial.check`, `tenant.blocked`

Frontend: JWT em `localStorage` (`auth-token`) + cookie espelhado; `AuthProvider` + middleware Next.

## Realidade mista (documentar, não “corrigir” aqui)

Nem todo endpoint segue Controller→Service→Repository. Existem controllers com Eloquent/`DB` direto, validação inline e responses manuais. Specs e implementação nova devem **seguir o padrão maduro do domínio vizinho**, não inventar uma terceira variante.
