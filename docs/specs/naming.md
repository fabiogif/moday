# Naming conventions — atuais

## Backend

| Tipo | Convenção | Exemplo |
|------|-----------|---------|
| Controller API | `*ApiController` (preferido) | `OrderApiController`, `ProductApiController` |
| Controller admin (código sem rota) | `*Controller` | `PermissionController` (`Admin/`) |
| Service | `*Service` | `OrderService`, `ProductService` |
| Repository | `*Repository` | `OrderRepository` |
| Interface repo | `*RepositoryInterface` em `Contracts/` | `OrderRepositoryInterface` |
| Form Request | `Store*`, `Update*` | `StoreOrderRequest`, `UpdateOrderRequest` |
| Resource | `*Resource` | `ProductResource` |
| Middleware | PascalCase descritivo | `EnsureTenantAccess`, `CheckPermission` |
| Test Feature | `*Test` | `CategoryTest` |
| Test Unit | `*Test` sob `Unit/` | `Services/AuthServiceTest` |
| Permission string | livre, checada via `hasPermission()` | ver `app/Models/Permission.php` para os valores reais em uso antes de inventar um novo |

Não existe convenção `Order*Service` para sub-serviços (não há sub-serviços), nem `plan.feature:{key}` (não existe esse middleware).

## Frontend

| Tipo | Convenção | Exemplo |
|------|-----------|---------|
| Page file | `page.tsx` no App Router | `orders/board/page.tsx` |
| Component file | kebab-case | `category-form-dialog.tsx` |
| Component export | PascalCase | `CategoryFormDialog` |
| Hook file | `use-*.ts` | `use-authenticated-api.ts` |
| Lib helper | kebab-case | `api-client.ts` |

Exceção conhecida: `src/components/PermissionGuard.tsx` é o único arquivo em PascalCase do projeto — não copiar esse nome, é histórico.

Não existe objeto `endpoints` centralizado nem convenção `endpoints.{modulo}.{acao}` — `api-client.ts` é uma classe `ApiClient`, não um mapa de paths.

## Banco

- Tabelas: `snake_case` plural (`orders`, `categories`)
- Colunas: `snake_case`
- Soft delete: `status` (ex.: `'A'`/`'I'` em `categories`) em alguns models, `deleted_at` em outros quando o model usa `SoftDeletes` — **não é uniforme**, verificar o model específico antes de assumir um padrão
- Escopo de tenant: coluna `tenant_id`, aplicado manualmente nas queries (sem trait automática)

## Idioma

Código e identifiers preferencialmente em inglês; mensagens de API/UI ao usuário em português (padrão das responses e toasts observado no código). Nomes de rota/pasta no frontend são majoritariamente em inglês — seguir o módulo vizinho.
