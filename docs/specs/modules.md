# Módulos — como novos módulos seguem a arquitetura existente

Baseado em módulos já implementados (Orders, Products/Categories, Financeiro, Loyalty/Marketing, iFood, Reports).

## Mapa atual (evidência)

| Módulo | Controllers (amostra) | Services | Bind repo |
|--------|----------------------|----------|-----------|
| Core (Cardápio/PDV/Tenant) | `ProductApiController`, `CategoryApiController`, `TableApiController`, `ClientApiController` | `ProductService`, `CategoryService`, … | `CoreRepositoryServiceProvider` |
| Orders (Pedidos) | `OrderApiController`, `OrderStatsApiController`, `OrderStatusApiController` | `OrderService` (facade) → `OrderCreationService`/`OrderQueryService`/`OrderWorkflowService`/`OrderLifecycleService` | `CoreRepositoryServiceProvider` |
| Financeiro | `AccountPayableApiController`, `AccountReceivableApiController`, `ExpenseApiController`, `FinancialCategoryApiController`, `BankAccountController` | `Services/Account*Service`, `ExpenseService`, `FinancialCategoryService` | `FinancialRepositoryServiceProvider` |
| Loyalty/Marketing | `LoyaltyClientApiController`, `LoyaltyProgramApiController`, `LoyaltyRewardApiController`, `CouponApiController`, `NewsletterApiController` | `LoyaltyPointsService`, `LoyaltyProgramService`, `LoyaltyRedemptionService`, `LoyaltyRewardService` | `LoyaltyRepositoryServiceProvider` / `MarketingRepositoryServiceProvider` |
| iFood (Integrations) | `Api/Integrations/Ifood/*` (`IfoodAuthController`, `IfoodCatalogController`, `IfoodOAuthController`, `IfoodOrderController`, `IfoodWebhookController`) | `Services/Integrations/Ifood/*` + Ports/Adapters | `IntegrationRepositoryServiceProvider` |
| Usuários/Perfis/Permissões | `UserApiController`, `RoleApiController`, `PermissionApiController`, `PermissionProfileApiController` | `UserService`, `RoleService`, `PermissionService`, `PermissionProfileService` | `CoreRepositoryServiceProvider` |
| Reports | Form Request/filtros → `Builders/*` | `ReportService` + `Reports/Builders`, `Reports/Queries`, `Reports/Exporters` | queries próprias |
| Dashboard/Analytics | `DashboardApiController`, `DashboardMetricsController`, `SalesPerformanceController` | `CacheService`, `ListingCacheService` | `AnalyticsRepositoryServiceProvider` |
| Notificações | — | — | `NotificationRepositoryServiceProvider` |
| PDV | `PDVFeedbackController`, `TableApiController` | services dedicados | `PDVRepositoryServiceProvider` |
| Admin (painel interno) | `Admin/*` (Blade/Inertia/Livewire), `Api/Admin/*` | services dedicados | `AdminRepositoryServiceProvider` |

Frontend espelha em `app/(dashboard)/…` com pastas **planas** por módulo (`orders`, `orders/board`, `products`, `financial/*`, `loyalty/*`, `integrations`, etc.) — o agrupamento por área (Cardápio, Financeiro, Marketing) é só visual no sidebar (`app-sidebar.tsx`), não estrutura de pastas.

## Backend — checklist de um módulo novo

Espelhar um módulo vizinho maduro (Products para domínio simples, Orders para domínio com múltiplos sub-fluxos):

1. Rotas em `routes/api.php` com `auth:api`, `tenant.blocked`, `trial.check`, e se aplicável `acl.permission:*` / `plan.feature:*` / `plan.order_limit` / `plan.user_limit`
2. Controller fino em `Http/Controllers/Api/`
3. Service em `Services/` (flat); só dividir em sub-services se o domínio já tiver a complexidade de Orders
4. `RepositoryInterface` + `Repository` (estendendo `BaseRepository`/`BaseRepositoryInterface` quando fizer sentido) + bind no provider do domínio (`bootstrap/providers.php`)
5. Form Request no estilo do vizinho (padrão dominante — 108 classes já existem)
6. Resposta via `ApiResponseClass`
7. Feature test em `tests/Feature/`

### Referências

- Controller→Service-facade→Sub-services→Repo: `OrderApiController` / `OrderService` / `OrderCreationService` / `OrderRepositoryInterface`
- Controller→Service simples→Repo: `ProductApiController` / `ProductService` / `ProductRepositoryInterface`
- Integração externa com Ports/Adapters: `Services/Integrations/Ifood/*` + `Ports/Integrations/Ifood/*` + `Adapters/Integrations/Ifood/Http/*`
- Report: `Reports/Builders/DailySalesReportBuilder` + `ReportService` + `Reports/Exporters/PdfExporter`
- Cache de listagem/métricas: `CacheService`/`ListingCacheService` injetado no service (ex.: `OrderCreationService`)

## Frontend — checklist

1. Página em `src/app/(dashboard)/{area}/` (pasta plana, sem aninhar sob um "grupo")
2. Paths em `endpoints` (`lib/api-client.ts`)
3. `useAuthenticatedApi` / `useMutation` (ou `useMutationWithValidation` se o form precisa mapear erro 422)
4. Forms: Zod + RHF + `components/ui`
5. Componentes em `{area}/components/` (padrão dominante) ou `src/components/{domínio}/` só se for cross-cutting
6. Toast do padrão do projeto
7. Se a tela precisa de atualização ao vivo: `useRealtimeOrders`/Echo, seguindo o padrão do Kanban (`orders/board`)

Referências: `orders/board/page.tsx` (Kanban + dnd-kit + realtime), `products/new/page.tsx` (form), `clients/components/client-form-dialog.tsx`.

## O que o código atual não usa como padrão de módulo

- Ports/Adapters fora de Email e iFood
- `*ServiceInterface` obrigatório
- React Query / Redux
- Exceções de domínio customizadas (usar `\DomainException` genérica)
- HTML de PDF via Blade — PDF é HTML montado por código em `PdfExporter`
- `src/hooks/use-api.ts` — legado morto, não copiar
