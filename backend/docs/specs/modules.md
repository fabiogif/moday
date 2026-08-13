# Módulos — como novos módulos seguem a arquitetura existente

Baseado em módulos já implementados (Sale, Logistics, Stock, Barcode, Reports).

## Mapa atual (evidência)

| Módulo | Controllers (amostra) | Services | Bind repo |
|--------|----------------------|----------|-----------|
| Core | `ProductApiController`, `ClientApiController` | `ProductService`, … | `CoreRepositoryServiceProvider` |
| Sale | `SaleOrderApiController`, `PickingApiController` | `Services/Sale/*` | `SaleOrderRepository` (Distribtec) |
| Logistics | `DeliveryRouteController`, `ShipmentPdfController`, drivers/vehicles | `Services/Logistics/*` | `ShipmentRepository` |
| Stock | `StockMovementApiController`, `BatchApiController` | `Services/Stock/*` | Batch, Warehouse, StockMovement |
| Barcode | `ProductBarcodeLookupController` | `Services/Barcode/*` | model `BarcodeLookup` |
| Reports | `ReportController`, `DistribtecReportController` | `Reports/*` + `ReportService` | queries próprias |
| Financial / Loyalty / Marketing / PDV / Admin | controllers + providers respectivos | services dedicados | providers em `bootstrap/providers.php` |

Frontend espelha em `app/(dashboard)/…` (`products`, `logistics/shipments`, `sale-orders`, etc.).

## Alterações no módulo Sale (Pedido / SaleOrder)

Hub crítico: `SaleOrderApiController` → `SaleOrderService` → `SaleOrderRepository`.  
Antes de alterar Pedido, seguir o playbook em `.planning/pedido-venda-playbook.md` (Graphify → impacto → uma fatia por PR → preservar `/offers/evaluate` e `/offline/sync` → regressão API + web + mobile).

Não misturar com `Order` (PDV/legado). Planos irmãos: `.planning/offline-pedidos-venda.md`, `.planning/ofertas-automaticas.md`.

## Backend — checklist de um módulo novo

Espelhar um módulo vizinho maduro (Stock ou Logistics):

1. Rotas em `routes/api.php` com `auth:api`, `tenant.blocked`, `trial.check`, e se aplicável `acl.permission:*` / `plan.feature:*`
2. Controller fino em `Http/Controllers/Api/`
3. Service em `Services/{Dominio}/`
4. `RepositoryInterface` + `Repository` + bind no provider do domínio
5. Form Request ou `validate()` no estilo do vizinho
6. Resposta via `ApiResponseClass`
7. Feature test em `tests/Feature/`

### Referências

- Controller→Service→Repo: `StockMovementApiController` / `StockMovementService` / `StockMovementRepository`
- Client externo: `DeliveryRouteService` + `GoogleMapsRoutingClient`
- PDF: `ShipmentPdfController` → `ShipmentPdfService` → Blade
- Lookup + DTO: `BarcodeLookupService` + `BarcodeProductData`

## Frontend — checklist

1. Página em `src/app/(dashboard)/{area}/`
2. Paths em `endpoints` (`lib/api-client.ts`)
3. `useAuthenticatedApi` / `useMutation`
4. Forms: Zod + RHF + `components/ui`
5. Componentes em `…/components/` ou `src/components/{domínio}/`
6. Toast do padrão do projeto

Referências: `logistics/shipments/page.tsx`, `products/new/page.tsx`, `hooks/use-suppliers.ts`.

## O que o código atual não usa como padrão de módulo

- Ports/Adapters fora de Email
- `*ServiceInterface` obrigatório
- React Query / Redux
- HTML de PDF no controller
