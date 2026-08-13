# Naming conventions — atuais

## Backend

| Tipo | Convenção | Exemplo |
|------|-----------|---------|
| Controller API | `*ApiController` (preferido) | `OrderApiController`, `ProductApiController` |
| Controller admin (Blade/Inertia) | `*Controller` | `PermissionController` (`Admin/`) |
| Service | `*Service` | `OrderService`, `ProductService` |
| Sub-service (domínio grande) | `Order*Service` | `OrderCreationService`, `OrderWorkflowService` |
| Repository | `*Repository` | `OrderRepository` |
| Interface repo | `*RepositoryInterface` em `Contracts/` | `OrderRepositoryInterface` |
| Form Request | `Store*`, `Update*` | `StoreOrderRequest`, `UpdateOrderRequest` |
| Resource | `*Resource` | `ProductResource` |
| Middleware | PascalCase descritivo | `CheckPlanFeatures`, `CheckTenantBlocked` |
| Migration | `YYYY_MM_DD_HHMMSS_snake_description.php` | `2026_07_19_000002_create_delivery_fee_zones_table.php` |
| Test Feature | `*Test` | `AccountPayableApiTest` |
| Test Unit | `*Test` sob `Unit/` | `Services/*Test` |
| Permission string | `resource.action` | `users.index` |
| Plan feature key | `snake_case` | `reports` (usado em `plan.feature:reports`) |

## Frontend

| Tipo | Convenção | Exemplo |
|------|-----------|---------|
| Page file | `page.tsx` no App Router | `orders/board/page.tsx` |
| Component file | kebab-case | `order-form-dialog.tsx` |
| Component export | PascalCase | `OrderFormDialog` |
| Hook file | `use-*.ts` | `use-authenticated-api.ts` |
| Lib helper | kebab-case | `api-config.ts` |
| Endpoint key | camelCase nested | `endpoints.orders.board` |
| Rota URL | kebab-case (mix PT/EN existente) | `/financial/accounts-payable`, `/contas-bancarias` |

## Banco

- Tabelas: `snake_case` plural (`orders`, `financial_categories`)
- Colunas: `snake_case`
- Soft delete: `deleted_at` quando o model usa SoftDeletes
- Escopo de tenant: coluna `tenant_id` + trait `BelongsToTenant`

## Idioma

Código e identifiers preferencialmente em inglês; mensagens de API/UI ao usuário em português (já é o padrão das responses e toasts). Nomes de rota/pasta no frontend têm mix PT/EN já existente — seguir o módulo vizinho, não forçar tradução em massa.
