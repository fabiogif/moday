# Glossário

Termos como usados neste repositório.

| Termo | Significado |
|-------|-------------|
| Tenant | Empresa/cliente da plataforma SaaS; isolamento por `tenant_id` |
| Plan feature | Capacidade habilitada no plano (`plan.feature:*`) |
| ACL / permission | Permissão nomeada no perfil do usuário (`acl.permission:*`) |
| Romaneio / Shipment | Agrupamento de pedidos para entrega (`shipments`) |
| Delivery sequence | Ordem das paradas (`delivery_sequence` / `optimized_route`) |
| Route order source | `system` (otimizado) ou `manual` (arraste) |
| POD | Proof of Delivery — comprovante no pivot / token público |
| Sale order | Pedido de venda Distribtec (`sale_orders`) vs `orders` legado |
| Barcode lookup | Consulta EAN (local → cache → Cosmos → Open Food Facts) |
| ApiResponseClass | Helper de envelope JSON da API |
| AuthTenantService | Resolve usuário autenticado + `tenant_id` |
| endpoints | Mapa de paths no frontend (`api-client.ts`) |
| useAuthenticatedApi | Hook de GET com cache e auth |
| useMutation | Hook de POST/PUT/PATCH/DELETE |
| DomPDF | Gerador de PDF usado com Blade |
| Distribtec | Linha de módulos WMS/logística/financeiro do produto |

Ver também `docs/specs/modules.md` para mapa de pastas.
