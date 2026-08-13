# Arquitetura em Camadas — Backend Moday

## Fluxo obrigatório

```
Controller → Service → RepositoryInterface → Repository → Model
```

| Camada | Responsabilidade |
|--------|------------------|
| **Controller** | HTTP: validação de request, status codes, resources JSON |
| **Service** | Regras de negócio, orquestração, cache, logs de domínio |
| **RepositoryInterface** | Contrato de persistência (DIP) |
| **Repository** | Queries Eloquent, agregações, transações de dados |
| **Model** | Entidade Eloquent, scopes, mutators |

## Princípios SOLID

- **SRP**: Controller só HTTP; Service só negócio; Repository só persistência
- **OCP**: Extensões via novas implementações de interface
- **LSP**: Repositories substituíveis via interface sem quebrar Service
- **ISP**: Interfaces por domínio em `app/Repositories/Contracts/`
- **DIP**: Service e Controller dependem de `*Interface`, nunca de implementação concreta

## Checklist para novo módulo

1. `*RepositoryInterface` em `app/Repositories/Contracts/`
2. `*Repository` em `app/Repositories/`
3. `*Service` em `app/Services/`
4. `*ApiController` fino em `app/Http/Controllers/Api/`
5. Binding no `*RepositoryServiceProvider` apropriado

## Exemplos de referência

### CRUD — Products

- `ProductApiController` → `ProductService` → `ProductRepositoryInterface` → `ProductRepository`

### Métricas — Dashboard / Admin

- `AdminMetricsController` → `AdminMetricsService` → `AdminMetricsRepositoryInterface`
- `DashboardApiController` → `DashboardMetricsService` → repositórios de métricas

## Providers de binding

| Provider | Domínios |
|----------|----------|
| `CoreRepositoryServiceProvider` | Catálogo, tenant, pedidos, reviews, profiles |
| `AdminRepositoryServiceProvider` | Admin metrics, tenants, dashboard, auth |
| `LoyaltyRepositoryServiceProvider` | Fidelidade |
| `PDVRepositoryServiceProvider` | PDV |

## Auditoria

```bash
php artisan audit:layers
# ou
php scripts/audit-layer-architecture.php
```

O script reporta:

- Controllers API com `App\Models\*` ou `DB::` direto
- Controllers que injetam `*Repository` sem `*Service`
- Services com Eloquent sem `*RepositoryInterface`
- Repositories sem interface
- Interfaces sem binding em providers

## O que evitar

- `use App\Models\*` em controllers API (exceto route model binding documentado)
- Lógica de negócio ou queries SQL no controller
- Service instanciando repository concreto (`new ProductRepository`)
- Repository chamando outro repository (orquestrar no Service)

## Legado

Controllers em `app/Http/Controllers/Admin/` (Blade) estão marcados como `@deprecated`. Novas features devem usar a API REST em `app/Http/Controllers/Api/`.

## Validação em CI

```bash
# Auditoria de camadas + testes Admin
composer ci:architecture

# Apenas auditoria
composer audit:layers

# Apenas testes Admin
composer test:admin
```

O pipeline de CI deve falhar se `composer audit:layers` retornar código de saída diferente de zero. Interfaces de infraestrutura (`BaseRepositoryInterface`, `PaginateRepositoryInterface`, `DetailPlanRepositoryInterface`) são excluídas da contagem de bindings.

### Meta de conformidade (módulos críticos)

| Módulo | Status |
|--------|--------|
| Products, Categories, Orders | Conforme |
| Admin (Metrics, Tenant, Dashboard, Auth, Email, PlanMigration) | Conforme |
| Users, Reviews, Plans, Profile, Subscription | Conforme |
| Loyalty, Coupons (stats), iFood | Conforme |

Services auxiliares legados (WhatsApp, TenantRegistration, PublicClient, etc.) permanecem fora do escopo incremental.
