# Frontend — especificações atuais

Baseado exclusivamente no código em `moday_frontend/`.

## Stack

- Next.js App Router
- React + TypeScript
- shadcn/ui (`components/ui`, Lucide)
- react-hook-form (`^7.62`) + zod (`^4.0`)
- sonner / toasts do projeto
- laravel-echo + pusher-js (transporte) com broadcaster `reverb` (Laravel Reverb, não Pusher Cloud)
- Jest (via `next/jest`) + Testing Library

Alias: `@/*` → `./src/*`

## Estrutura

| Path | Papel |
|------|--------|
| `src/app/(dashboard)/` | App autenticado do tenant — pastas **planas** por módulo (não há aninhamento tipo `menu/produtos`; agrupamento visual só existe no sidebar) |
| `src/app/auth/` | Login/cadastro **real** (`login`, `register`, `forgot-password`, `reset-password`) |
| `src/app/(auth)/` | Template shadcn legado — **não usado** pelo middleware/roteamento real, não copiar padrões daqui |
| `src/app/admin/` | Painel admin — auth e proteção **client-side** (`AdminAuthProvider`), o `middleware.ts` não protege `/admin` |
| `src/app/store/[slug]/` | Loja pública (menu/cart do cliente final) |
| `src/app/api/` | Route Handlers — usados **só pela landing page** (contato/newsletter/analytics); não é BFF do dashboard, não existe pasta `internal-api/` |
| `src/components/ui/` | Primitivos shadcn |
| `src/components/<domínio>/` | Reservado para componentes **cross-cutting** (`admin/`, `forms/`, `landing/`, `layouts/`, `location/`, `notifications/`, `subscription/`, `theme-customizer/`) |
| `.../<rota>/components/` | Local **predominante** para componentes de domínio — colocados perto da rota (38 pastas assim) |
| `src/hooks/` | `use-authenticated-api.ts` (ativo), `use-realtime*.ts` (Echo), hooks de domínio |
| `src/lib/api-client.ts` | Cliente HTTP tenant + `endpoints` |
| `src/lib/admin-api-client.ts` | Cliente HTTP separado do painel admin |
| `src/lib/echo.ts` | Cliente Laravel Echo/Reverb |
| `src/contexts/` | `auth-context.tsx`, `admin-auth-context.tsx`, sidebar/tema/notificações |

## Fluxo de página dashboard

```
middleware.ts (cookie auth-token, allow-list em auth-routes.ts)
  → RootLayout (AuthProvider em src/app/layout.tsx — engloba toda a app, não só o dashboard)
  → (dashboard)/layout (AuthGuard → OrderNotificationsProvider → POSHeaderProvider → SidebarProvider, useAuthSync)
  → page.tsx ("use client")
  → useAuthenticatedApi / useMutation + Form (RHF+Zod) + ui/
  → apiClient → Laravel /api/...
```

## API client

- Singleton `apiClient` em `src/lib/api-client.ts`; base URL via `src/lib/api-config.ts` (`NEXT_PUBLIC_API_URL`, vazio em prod = same-origin via Nginx)
- Paths centralizados em `endpoints.*` (objeto único, ~450 linhas, cobre todos os domínios) — não inlinar URL de API em componente novo
- Envelope: `{ success, message, data, meta? }`
- Bearer de `localStorage['auth-token']`; cookie espelhado (não httpOnly) para o middleware ler
- FormData: não forçar `Content-Type`
- 401 → limpa token + evento `window` `auth:unauthorized` (capturado pelo `AuthProvider` para forçar logout)

Hooks padrão (`src/hooks/use-authenticated-api.ts` — **este é o hook ativo**, 32 importadores):

```ts
const { data, loading, refetch } = useAuthenticatedApi<T>(endpoints.foo.list, { ttl })
const { mutate, loading: saving } = useMutation()
await mutate(endpoints.foo.create, 'POST', payload)
// erros 422 do Laravel mapeados para RHF:
const { mutate } = useMutationWithValidation()
```

- Cache em memória por `endpoint::queryParams` com `invalidateCache(pattern?)`; TTL tunado por domínio (~60s dados estáticos como produtos/categorias, ~30s tabelas/clientes, ~15s pedidos do dia)
- **`src/hooks/use-api.ts` é código morto** (0 importações reais fora de mocks de teste) — não usar como referência em código novo, é candidato a remoção

Admin: `admin-api-client.ts` (`adminApi`, chama `/api/admin{endpoint}`) + `admin-auth-context.tsx` (`useAdminAuth`), token `admin-token` em `localStorage`.

## Auth

- `AuthProvider` (`src/contexts/auth-context.tsx`): token (`auth-token`) + user (`auth-user`) + `trial-status`, montado no `RootLayout`
- Cookie `auth-token` espelhado (client-side, não httpOnly) para o `middleware.ts` ler
- `useAuthSync` (`src/hooks/use-auth-sync.ts`) no layout dashboard re-sincroniza token no `apiClient`
- Guards: `middleware.ts` (allow-list via `src/lib/auth-routes.ts`) + `AuthGuard` (`src/components/auth-guard.tsx`) + `useTrialGuard`
- Admin: fluxo isolado (`AdminAuthProvider`, `admin-token`), sem middleware Next — proteção só client-side

## Formulários

1. Schema Zod na page/componente
2. `useForm({ resolver: zodResolver(schema) })`
3. Componentes `Form` / `FormField` / `FormMessage` do shadcn
4. Submit via `useMutation` (JSON ou `FormData`)
5. Erros de API 422: `useMutationWithValidation` (mapeia para `setError` do RHF)
6. Feedback: toast

Exemplos reais: `orders/components/order-form-dialog.tsx`, `clients/components/client-form-dialog.tsx`, `users/components/user-form-dialog.tsx`, `auth/register/components/register-form.tsx`.

## Componentes

- Arquivo kebab-case: `order-form-dialog.tsx` → export PascalCase `OrderFormDialog` (padrão predominante; exceção conhecida: `src/components/PermissionGuard.tsx`, PascalCase no nome do arquivo)
- Colocar perto da rota (`.../orders/components/`) — é o padrão dominante (38 ocorrências); usar `src/components/<domínio>/` só para o que já é cross-cutting
- Dialogs CRUD: `*-form-dialog.tsx`, `delete-*-dialog.tsx`

## Estado

- Preferir `useState` + hooks de API
- Context para auth/admin-auth/shell/notificações
- Zustand só onde já existe: **3 stores reais** — `chat/use-chat.ts`, `mail/use-mail.ts`, `hooks/use-order-refresh.ts` (sinaliza refetch da lista de pedidos). Não existe store de carrinho — o carrinho da loja pública (`store/[slug]/page.tsx`) é `useState<CartItem[]>`

Não introduzir React Query/Redux sem necessidade comprovada no domínio.

## Tempo real (WebSocket)

- `src/lib/echo.ts`: laravel-echo + pusher-js como transporte, broadcaster configurado como `reverb` (env `NEXT_PUBLIC_REVERB_*`)
- `useRealtimeOrders` (`src/hooks/use-realtime.ts`) — inscreve em `tenant.{id}.orders`, eventos `.order.created`/`.order.updated`/`.order.status.updated`; único consumidor confirmado é o Kanban (`orders/board/page.tsx`)
- `usePresence` para `tenant.{id}.presence`; `use-realtime-dashboard.ts` para métricas ao vivo

## Drag and drop

- `@dnd-kit/core` + `@dnd-kit/utilities` usados só no Kanban de pedidos (`orders/board/page.tsx`, arquivo único, sem `board/components/`) — `DndContext`, `DragOverlay`, `useDroppable`/`useDraggable`, `closestCorners`, `PointerSensor`
- `@dnd-kit/sortable`/`@dnd-kit/modifiers` estão instalados mas não confirmados em uso nessa tela — checar o módulo antes de assumir que estão ativos em outro lugar

## Imagens

- Ver módulo de produtos/cardápio para padrão de upload (`FormData` com `image`/`image_url`) — seguir o componente vizinho já existente

## Env

- Browser: `NEXT_PUBLIC_API_URL`, `NEXT_PUBLIC_SITE_URL`, `NEXT_PUBLIC_APP_ENV`, `NEXT_PUBLIC_MERCADOPAGO_PUBLIC_KEY`, `NEXT_PUBLIC_REVERB_HOST`/`_PORT`/`_SCHEME`/`_APP_KEY`, `NEXT_PUBLIC_GA_MEASUREMENT_ID`
- Server: `API_URL_INTERNAL` (SSR/Route Handlers)
- Sem chave de mapas/geocoding — CEP e CNPJ passam pelo proxy do backend (`/api/cep/{cep}`, `/api/cnpj/{cnpj}`); o frontend usa `src/services/viacep.ts` e `src/services/receitaws.ts` apenas como clients internos
- Estados/municípios: catálogo local IBGE via `/api/states` e `/api/states/{id}/cities` (`useStates` / `useCitiesByState` + `StateCityFormFields` / `StateCitySelect`); autofill de CEP com `applyCepToForm` / `applyCepToStateHandlers`

## Naming de rotas

- Kebab-case predominante, mix PT/EN já existente (`accounts-payable`, `sales-performance`, `contas-bancarias`, `configuracoes/status-pedidos`) — seguir o módulo vizinho
- Dinâmicos: `[id]`, `[slug]`, `[token]`

## Testes

- Jest + jsdom via `next/jest`, alias `@/` configurado, `jest.setup.js`
- 96 arquivos de teste: centralizados em `src/__tests__/` (`app/`, `components/`, `contexts/`, `cruds/` por domínio, `hooks/`, `lib/`, `permissions/`, `pos/`) + colocados em `.../__tests__/` perto da rota/componente
- `src/__tests__/utils/test-utils.tsx` é o harness compartilhado: mocka `@/lib/api-client`, **todos** os hooks de `@/hooks/use-authenticated-api`, `@/hooks/use-api` (legado) e expõe geradores de dados (`generateUser`, `generateOrder`, etc.)
