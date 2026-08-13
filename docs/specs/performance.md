# Performance — práticas atuais

## Backend

- Eager load (`with([...])`) nos repositories/services para evitar N+1 quando o domínio já faz isso
- Cache via `CacheService`/`ListingCacheService`, com `remember(key, ttl, callback)` e fallback silencioso (loga erro e executa o callback direto se o cache falhar). TTLs reais (`CACHE_TTL` em `CacheService.php`): `order_stats` 900s, `dashboard_metrics`/`dashboard_revenue`/`recent_transactions` 300s, `sales_performance`/`top_products`/`order_data`/`order_list` 600s, `client_stats`/`product_stats` 1800s, `category_stats`/`table_stats`/`payment_method_stats`/`role_list` 3600s, `permission_list` 7200s
- Invalidação por domínio: `invalidateClientCache`, `invalidateProductCache`, `invalidateOrderCache`, `invalidateCategoryCache`, `invalidateAllTenantCache`, etc. — chamar a invalidação do domínio correto no write, não `invalidateAllTenantCache` por padrão
- Paginação em índices via `PaginateRepositoryInterface`/`PaginatePresenter`
- Throttle diferenciado: leituras `throttle:read`, mutações críticas `throttle:critical` (ver `security.md` sobre `throttle:write` estar quebrado)

## Frontend

- Cache em memória do `useAuthenticatedApi` + `refetch`/invalidação pontual após mutação — evitar recarregar a página inteira
- Realtime via Echo/Reverb (`useRealtimeOrders`, `use-realtime-dashboard.ts`) evita polling no Kanban de pedidos e nas métricas do dashboard
- `"use client"` só onde necessário
- Evitar re-fetch em loop

## Banco

- Índices via migrations existentes; novas queries filtradas por `tenant_id` (aplicado manualmente, sem trait automática — ver `security.md`)
- Evitar `DB::` solto em hot paths de controller — preferir repository com query enxuta

## Checklist

- [ ] Listagem paginada
- [ ] `with()` nas relações usadas na resposta
- [ ] Cache invalidado no domínio correto se o service usa `CacheService`/`ListingCacheService`
- [ ] Sem N+1 óbvio na feature
- [ ] Frontend não dispara requests duplicados no mount
