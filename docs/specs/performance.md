# Performance — práticas atuais

## Backend

- Eager load (`with([...])`) nos repositories/services para evitar N+1 (padrão em listagens maduras)
- Cache via `CacheService`/`ListingCacheService` em vários services (pedidos, dashboard, produtos, sales-performance) — TTL nomeado por domínio (`order_stats` 900s, `dashboard_metrics` 300s, `sales_performance` 600s, `*_list` para listagens); invalidar no write via `CacheInvalidationMiddleware`/injeção direta do service
- Paginação em índices via `PaginateRepositoryInterface`/`PaginatePresenter` (não `LengthAwarePaginator` cru quando o domínio já usa esse contrato)
- Throttle diferenciado: leituras `throttle:read`, mutações críticas `throttle:critical`, escrita geral `throttle:write`
- Jobs/queues quando já existirem no domínio (não criar fila "por padrão")
- Integrações externas (iFood): adapters encapsulados (`Http` facade) — não travar o request principal em chamada síncrona longa sem necessidade

## Frontend

- Cache em memória do `useAuthenticatedApi` (TTL tunado por domínio: ~60s estático, ~30s semi-estático, ~15s dinâmico como pedidos do dia) + `invalidateCache(pattern?)` / `refetch` após mutate
- Realtime via Echo/Reverb (`useRealtimeOrders`) evita polling no Kanban de pedidos
- `"use client"` só onde necessário
- Evitar re-fetch em loop; usar `refetch`/`invalidateCache` pontual em vez de recarregar tudo

## PDF / reports

- `PdfExporter` monta HTML por concatenação e chama DomPDF sob demanda — não gerar PDF em listagens/loops
- Pipeline `Builders → Queries → ReportService → Exporters` — filtrar na Query, não em memória depois

## Banco

- Índices via migrations existentes; novas queries filtradas por `tenant_id`
- Evitar `DB::` solto em hot paths de controller — preferir repository com query enxuta

## Checklist

- [ ] Listagem paginada
- [ ] `with()` nas relações usadas na resposta
- [ ] Cache invalidado se o domínio usa `CacheService`/`ListingCacheService`
- [ ] Sem N+1 óbvio na feature
- [ ] Frontend não dispara requests duplicados no mount
