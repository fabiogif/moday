# Performance — práticas atuais

## Backend

- Eager load (`with([...])`) nos repositories/services para evitar N+1 (padrão em listagens maduras)
- Cache de listagens via `CacheService` em vários services (products, stock movements, etc.) — invalidar no write
- Paginação em índices (`paginate` / `per_page` limitado)
- Throttle diferenciado: leituras `throttle:read`, mutações críticas `throttle:critical`
- Jobs/queues quando já existirem no domínio (não criar fila “por padrão”)
- Geocode/Maps: clients dedicados + fallback heurístico (rotas) para não travar request

## Frontend

- Cache em memória do `useAuthenticatedApi` (TTL ~30s) + `invalidateCache` / refetch após mutate
- `dynamic(..., { ssr: false })` para mapas pesados (ex.: `RouteMap`)
- `"use client"` só onde necessário; layouts server quando já forem
- Imagens: compressão POD (`compressDeliveryPhoto`); não carregar blobs gigantes sem necessidade
- Evitar re-fetch em loop; usar `refetch` pontual

## PDF / reports

- Views Blade enxutas; DomPDF é custoso — gerar sob demanda (stream/save), não em listagens

## Banco

- Índices via migrations existentes; novas queries filtradas por `tenant_id`
- Evitar `DB::` solto em hot paths de controller — preferir repository com query enxuta

## Checklist

- [ ] Listagem paginada
- [ ] `with()` nas relações usadas na resposta
- [ ] Cache invalidado se o domínio usa `CacheService`
- [ ] Sem N+1 óbvio na feature
- [ ] Frontend não dispara requests duplicados no mount
