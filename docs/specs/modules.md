# Módulos — como novos módulos seguem a arquitetura existente

Baseado nos módulos realmente implementados em `backend/` + `frontend/`.

## Mapa atual (evidência)

| Módulo | Controllers (Api/) | Service | Repository |
|--------|---------------------|---------|------------|
| Cardápio | `ProductApiController`, `CategoryApiController`, `TableApiController` | `ProductService`, `CategoryService`, `TableService` | `ProductRepository`, `CategoryRepository`, `TableRepository` |
| Pedidos | `OrderApiController`, `OrderStatsApiController` | `OrderService` (classe única, sem sub-services) | `OrderRepository` |
| Clientes / loja pública | `ClientApiController`, `ClientAuthController`, `PublicStoreController` | `ClientService` | `ClientRepository` |
| Usuários/Perfis/Permissões | `UserApiController`, `UserStatsApiController`, `RoleApiController`, `PermissionApiController`, `PermissionProfileApiController`, `ProfileApiController` | `UserService`, `PermissionService` | `UserRepository`, `PermissionRepository` |
| Planos | `PlanApiController`, `DetailPlanApiController` | `PlanService`, `DetailPlanService` | `PlanRepository`, `DetailPlanRepository` |
| Pagamentos | `PaymentMethodApiController` | `PaymentMethodService` | `PaymentMethodRepository` |
| Avaliações | `EvaluationApiController` | `EvaluationService` | `EvaluationRepository` |
| Dashboard | `DashboardApiController`, `DashboardMetricsController` | `DashboardService`, `DashboardMetricsService` | `DashboardRepository`, via `CacheService` |
| Tenant | `TenantApiController` | `TenantService` | `TenantRepository` |
| Admin (código presente, sem rota) | `Admin/PermissionController`, `Admin/ProfileController`, `Admin/PlanController`, `Admin/PlanProfileController`, `Admin/PermissionProfileController`, `Admin/DetailPlanController` | — | — |

**Não existem** os módulos: Financeiro, Loyalty/Marketing, iFood/Integrations, Reports, PDV, Notificações. Se um pedido de feature mencionar algum desses, tratar como módulo **novo**, não como extensão de algo existente.

Frontend espelha em `app/(dashboard)/…` com pastas planas por módulo (`orders`, `orders/board`, `products`, `categories`, `clients`, `users`, `permissions`, `profiles`, `tables`, `payment-methods`, `reports` — esta última é só uma página de UI, não tem pipeline de builders/exporters no backend correspondente).

## Backend — checklist de um módulo novo

Espelhar um módulo vizinho maduro (ex.: `Category` ou `Product` para domínio simples; não há domínio complexo com sub-services para copiar):

1. Rotas em `routes/api.php` com `auth:api`, throttle apropriado (`read`/`critical`), e `acl.permission:*` se o módulo exigir
2. Controller fino em `Http/Controllers/Api/`
3. Service único em `Services/` (flat) — não criar sub-services sem necessidade real comprovada
4. `RepositoryInterface` + `Repository` estendendo `BaseRepository`/`BaseRepositoryInterface`, bind em `RepositoryServiceProvider`
5. Form Request no estilo do vizinho
6. Resposta via `ApiResponseClass`
7. Feature test em `tests/Feature/`

### Referência real

`ProductApiController` / `ProductService` / `ProductRepositoryInterface` / `ProductRepository` — este é o padrão a copiar para qualquer módulo novo simples.

## Frontend — checklist

1. Página em `src/app/(dashboard)/{area}/` (pasta plana, sem aninhar sob um "grupo")
2. Chamadas via `use-authenticated-api.ts` (nunca `use-api.ts`)
3. Forms: Zod + RHF + `components/ui`
4. Componentes em `{area}/components/` (padrão dominante) ou `src/components/{domínio}/` só se for cross-cutting
5. Se a tela precisa de atualização ao vivo: seguir o padrão de `orders/board` (Echo/Reverb via `use-realtime.ts`)

Referências: `orders/board/page.tsx` (Kanban + dnd-kit + realtime), `categories/components/category-form-dialog.tsx` (form).

## O que o código atual não usa como padrão de módulo

- Service-facade + sub-services
- Ports/Adapters, integrações externas
- `*ServiceInterface` obrigatório
- React Query / Redux
- Exceções de domínio customizadas
- `src/hooks/use-api.ts` — legado em migração, não copiar
