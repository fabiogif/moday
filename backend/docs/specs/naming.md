# Naming conventions — atuais

## Backend

| Tipo | Convenção | Exemplo |
|------|-----------|---------|
| Controller API | `*ApiController` (preferido) | `ProductApiController` |
| Controller API (alt.) | `*Controller` | `DeliveryRouteController` |
| Service | `*Service` | `DeliveryRouteService` |
| Repository | `*Repository` | `ShipmentRepository` |
| Interface repo | `*RepositoryInterface` em `Contracts/` | `ShipmentRepositoryInterface` |
| Form Request | `Store*`, `Update*`, `StoreUpdate*` | `StoreUpdateProductRequest` |
| Resource | `*Resource` | `ProductResource` |
| Middleware | PascalCase descritivo | `CheckPlanFeatures` |
| Migration | `YYYY_MM_DD_HHMMSS_snake_description.php` | `2026_07_22_120001_add_route_order_source_to_shipments.php` |
| Test Feature | `*Test` / `*ApiTest` | `DeliveryRouteApiTest` |
| Test Unit | `*Test` sob `Unit/` | `BarcodeLookupServiceTest` |
| Permission string | `resource.action` | `products.index` |
| Plan feature key | `snake_case` | `delivery_routing` |

## Frontend

| Tipo | Convenção | Exemplo |
|------|-----------|---------|
| Page file | `page.tsx` no App Router | `logistics/shipments/page.tsx` |
| Component file | kebab-case | `route-stops-table.tsx` |
| Component export | PascalCase | `RouteStopsTable` |
| Hook file | `use-*.ts` | `use-authenticated-api.ts` |
| Lib helper | kebab-case | `fetch-image-as-file.ts` |
| Endpoint key | camelCase nested | `endpoints.shipments.pdf` |
| Rota URL | kebab-case | `/logistics/freight-weight` |

## Banco

- Tabelas: `snake_case` plural (`sale_orders`, `shipments`)
- Pivots: `shipment_sale_order`
- Colunas: `snake_case`
- Soft delete: `deleted_at` quando o model usa SoftDeletes

## Idioma

Código e identifiers preferencialmente em inglês; mensagens de API/UI ao usuário em português (já é o padrão das responses e toasts).
