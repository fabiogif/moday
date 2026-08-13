# Glossário

Termos como usados neste repositório (Moday).

| Termo | Significado |
|-------|-------------|
| Tenant | Empresa/restaurante cliente da plataforma; isolamento por coluna `tenant_id`, checado manualmente (sem trait dedicada) |
| ACL / permission | Permissão do usuário, checada via `User::hasPermission()` + middleware `acl.permission` (`PermissionMiddleware`) |
| Order (Pedido) | Entidade central do domínio de vendas (`orders`); um único `OrderService`, sem sub-services |
| Orders board / Kanban | Tela `orders/board` com drag-and-drop (`@dnd-kit`) por status de pedido, atualizada em tempo real via Reverb |
| Cardápio | Módulos de Produtos/Categorias/Mesas (`products`, `categories`, `tables`) |
| ApiResponseClass | Helper de envelope JSON da API (`App\Classes\ApiResponseClass`) |
| Guard `api` / `client` | Dois guards JWT reais: `api` (dashboard/tenant), `client` (loja pública). **Não existe** guard `admin` |
| ApiClient | Classe singleton do frontend que faz as chamadas HTTP autenticadas (`src/lib/api-client.ts`) |
| useAuthenticatedApi | Hook de fetch com cache/TTL e auth (hook ativo, ~30 importadores) — não confundir com o legado `use-api.ts` |
| Reverb | Servidor WebSocket (Laravel Reverb) usado para tempo real — substitui o Pusher Cloud, mesmo protocolo |
| CacheService / ListingCacheService | Wrappers de cache com TTL nomeado por domínio (`CACHE_TTL` em `CacheService.php`) |
| Admin/* | Namespace de controllers existente no código mas **sem rota registrada** — não é um painel funcional |

Termos que **não** existem neste projeto (evitar usar como se fossem reais): plan feature/plan limit, AuthTenantService, BelongsToTenant, iFood, Loyalty/Fidelidade, PDV, Report pipeline (Builders/Exporters), DomPDF em uso ativo, sub-services de Orders.

Ver também `docs/specs/modules.md` para mapa de pastas.
