# Frontend — especificações atuais

Baseado exclusivamente no código em `distribtec_frontend/`.

## Stack

- Next.js App Router
- React + TypeScript
- shadcn/ui (`components/ui`, style new-york, Lucide)
- react-hook-form + zod
- sonner / toasts do projeto
- Jest + Testing Library

Alias: `@/*` → `./src/*`

## Estrutura

| Path | Papel |
|------|--------|
| `src/app/(dashboard)/` | App autenticado do tenant |
| `src/app/(auth)/`, `src/app/auth/` | Login / cadastro |
| `src/app/admin/` | Painel admin |
| `src/app/store/[slug]/` | Loja pública |
| `src/app/delivery/[token]/` | POD motorista |
| `src/app/internal-api/` | Route handlers internos (ex.: image-search) |
| `src/components/ui/` | Primitivos shadcn |
| `src/components/<domínio>/` | Features reutilizáveis |
| `src/hooks/` | API e domínio |
| `src/lib/api-client.ts` | Cliente HTTP + `endpoints` |
| `src/contexts/` | Auth e UI shell |

## Fluxo de página dashboard

```
middleware (cookie auth-token)
  → RootLayout (AuthProvider, theme)
  → (dashboard)/layout (AuthGuard, sidebar, useAuthSync)
  → page.tsx ("use client")
  → useAuthenticatedApi / useMutation + Form (RHF+Zod) + ui/
  → apiClient → Laravel /api/...
```

## API client

- Singleton `apiClient` em `src/lib/api-client.ts`
- Paths centralizados em `endpoints.*`
- Envelope: `{ success, message, data, meta? }`
- Bearer de `localStorage['auth-token']`
- FormData: não forçar `Content-Type`
- 401 → limpa token + evento `auth:unauthorized`

Hooks padrão:

```ts
const { data, loading, refetch } = useAuthenticatedApi<T>(endpoints.foo.list)
const { mutate, loading: saving } = useMutation()
await mutate(endpoints.foo.create, 'POST', payload)
```

Admin: `admin-api-client` + `use-admin-api` (`admin-token`).

## Auth

- `AuthProvider` (`auth-context.tsx`): token + user + trial
- Cookie `auth-token` espelhado (middleware)
- `useAuthSync` no layout dashboard
- Guards: middleware Next + `AuthGuard` + trial

## Formulários

1. Schema Zod na page/componente
2. `useForm({ resolver: zodResolver(schema) })`
3. Componentes `Form` / `FormField` / `FormMessage` do shadcn
4. Submit via `useMutation` (JSON ou `FormData`)
5. Erros de API: `useBackendValidation` / `useMutationWithValidation`
6. Feedback: toast

## Componentes

- Arquivo kebab-case: `route-stops-table.tsx`
- Export PascalCase: `RouteStopsTable`
- Colocar perto da rota (`…/shipments/components/`) ou em `src/components/<domínio>/` se reutilizado
- Dialogs CRUD: `*-form-dialog.tsx`, `delete-*-dialog.tsx`

## Estado

- Preferir `useState` + hooks de API
- Context para auth/shell/notificações
- Zustand só onde já existe (chat, mail, store cart)

Não introduzir React Query/Redux sem necessidade comprovada no domínio.

## Imagens

- `resolveImageUrl` — path API → URL pública
- `fetchImageAsFile` — URL remota (+ proxy `internal-api/image-search?proxy=`) → `File`
- Upload produto: `FormData` com `image` e/ou `image_url`

## Env

- Browser: `NEXT_PUBLIC_*` (`NEXT_PUBLIC_API_URL`, Maps, Reverb, MercadoPago, etc.)
- Server: `API_URL_INTERNAL` quando necessário

## Naming de rotas

- Kebab-case: `sale-orders`, `freight-weight`
- Dinâmicos: `[id]`, `[uuid]`, `[token]`
- Mix PT/EN já existente (`funcionarios`, `products`) — seguir o módulo vizinho

## Testes

- Jest + jsdom
- Suites em `src/__tests__/` e colocadas por feature
- Mock de `useAuthenticatedApi` / `useMutation` é o padrão
