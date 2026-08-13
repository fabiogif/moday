# Glossário

Termos como usados neste repositório (Moday / marca **Alba Tec**).

| Termo | Significado |
|-------|-------------|
| Tenant | Empresa/restaurante cliente da plataforma SaaS; isolamento por `tenant_id` (trait `BelongsToTenant`) |
| Plan feature | Capacidade habilitada no plano (`plan.feature:*`, ex.: `plan.feature:reports`) |
| Plan limit | Limite quantitativo do plano (`plan.order_limit`, `plan.user_limit`) |
| ACL / permission | Permissão nomeada no perfil do usuário (`acl.permission:{resource.action}`, ex.: `users.index`) |
| Order (Pedido) | Entidade central do domínio de vendas (`orders`); fluxo com sub-services (`OrderCreationService`, `OrderWorkflowService`, `OrderLifecycleService`, `OrderQueryService`) |
| Orders board / Kanban | Tela `orders/board` com drag-and-drop (`@dnd-kit`) por status de pedido, atualizada em tempo real |
| PDV | Ponto de venda interno (`pdv/`, `PDVFeedbackController`, `TableApiController`) |
| Cardápio | Módulos de Produtos/Categorias/Mesas/Tipos de serviço (`products`, `categories`, `tables`, `service-types`) |
| iFood | Integração de delivery via Ports/Adapters (`Ports/Integrations/Ifood`, `Adapters/Integrations/Ifood/Http`, `Services/Integrations/Ifood`) |
| Loyalty / Fidelidade | Programa de pontos e recompensas (`LoyaltyProgramService`, `LoyaltyRewardService`, `LoyaltyRedemptionService`) |
| ApiResponseClass | Helper de envelope JSON da API (`App\Classes\ApiResponseClass`) |
| AuthTenantService | Resolve usuário autenticado + `tenant_id` (`requireAuthenticatedTenant()`) |
| Guard `api` / `client` / `admin` | Três guards de auth: `api` (JWT, tenant/dashboard), `client` (JWT, loja pública), `admin` (Sanctum, painel admin) |
| endpoints | Mapa de paths no frontend (`src/lib/api-client.ts`) |
| useAuthenticatedApi | Hook de GET com cache/TTL e auth (hook ativo — não confundir com o legado `use-api.ts`) |
| useMutation / useMutationWithValidation | Hooks de POST/PUT/PATCH/DELETE; a segunda mapeia erro 422 para o React Hook Form |
| Reverb | Servidor WebSocket (Laravel Reverb) usado para tempo real — substitui o Pusher Cloud, mesmo protocolo |
| DomPDF | Gerador de PDF; aqui usado com HTML montado por código, sem view Blade (`PdfExporter`) |
| Report pipeline | `Builders/*` → `Queries/*` → `ReportService` → `Exporters/*` (Pdf/Excel/Csv) |
| CacheService / ListingCacheService | Wrappers de cache com TTL nomeado por domínio (`CACHE_TTL`), invalidados por `CacheInvalidationMiddleware` |

Ver também `docs/specs/modules.md` para mapa de pastas.
