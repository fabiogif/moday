# Frontend — especificações atuais

Baseado exclusivamente no código em `frontend/`.

## Stack

- Next.js 15.4.7, App Router (não há `pages/`), React 19.1.0, TypeScript 5
- UI: shadcn/ui (`components.json`, style "new-york"), Radix primitives, `class-variance-authority`, `tailwind-merge`, `lucide-react`, Tailwind v4
- Forms: `react-hook-form` + `zod` + `@hookform/resolvers` — padrão dominante (30+ arquivos usam RHF, 35+ usam Zod)
- Tabelas: `@tanstack/react-table`; drag-and-drop: `@dnd-kit/*`
- Gráficos: `recharts`; datas: `date-fns`; toasts: `sonner`
- Tempo real: `laravel-echo` + `pusher-js` (broadcaster `reverb`)
- `zustand` está no `package.json` mas não há stores identificados sob `src/` além dos Contexts de auth — não citar Zustand como padrão de estado do projeto sem confirmar um uso real antes.

## Estrutura de rotas (`src/app/`)

- `(dashboard)/` — ~28 segmentos: `dashboard` (+ `dashboard-old`, remanescente duplicado — não usar como referência), `orders` (+ `board`, `edit`, `new`), `products` (+ `new`, `[id]`), `categories`, `clients`, `users`, `permissions`, `profiles`, `tables`, `tasks`, `calendar`, `chat`, `mail`, `faqs`, `pricing`, `reports`, `payment-methods`, `settings` (6 subpáginas)
- `(auth)/` — login/registro; contém variantes de template não consolidadas (`sign-in`, `sign-in-2`, `sign-up`, `sign-up-2`, `sign-up-3`, `forgot-password`, `forgot-password-2`, `forgot-password-3`) — ao mexer em auth, confirmar qual variante está de fato linkada antes de editar todas
- `landing/` — página pública de marketing
- `store/[slug]/` — loja pública (storefront), com `login`/`register`/`orders` próprios e auth de cliente separada
- `api/` — Route Handlers usados só pela landing page (auth/login, categories, orders, products, tables) — **não é um BFF do dashboard**

Total: 53 arquivos `page.tsx`. Não existe rota/painel `admin/`.

## API client

- `src/lib/api-client.ts` — classe `ApiClient` singleton feita à mão (JWT Bearer, base URL de `NEXT_PUBLIC_API_URL`); não há um objeto `endpoints` gerado/centralizado separado
- `src/hooks/use-authenticated-api.ts` (524 linhas) — hook **dominante**, ~30 importadores em clients/products/permissions/dashboard/orders/categories/users/profiles/forms
- `src/hooks/use-api.ts` (245 linhas) — **legado em migração**: só 2 usos reais (`orders/page.tsx`, e `orders/new/page.tsx` onde o import já está comentado como `// Temporário para teste`), fora isso só aparece em mocks de teste. Existe também um `use-api.ts.backup` órfão. **Nunca usar `use-api.ts` em código novo** — usar `use-authenticated-api.ts`.

## Autenticação

- JWT em `localStorage['auth-token']` **e** em cookie espelhado (não httpOnly), lido por `src/middleware.ts` (listas hardcoded de rotas públicas/protegidas)
- `src/contexts/auth-context.tsx` (`AuthProvider`/`useAuth`) — para usuários staff/dashboard
- `src/contexts/client-auth-context.tsx` (`ClientAuthProvider`) — **separado**, usado só nas páginas `store/[slug]/*` (auth de cliente da loja pública, não é um "admin guard")
- Único componente de guarda de UI: `src/components/PermissionGuard.tsx` (também o único arquivo em PascalCase do projeto — os demais são kebab-case; não replicar esse nome, é exceção histórica)

Não existem múltiplos "guards" de autenticação nem fluxo de admin isolado no frontend.

## Organização de componentes

- Colocados perto da rota: 35 pastas `components/` sob `src/app/**` (ex.: `orders/components/`, `orders/board/components/`) — padrão dominante para componentes de domínio
- Centralizado em `src/components/`: `ui/` (44 componentes shadcn), `forms/`, `layouts/`, `landing/`, `theme-customizer/`, mais alguns arquivos soltos compartilhados (`PermissionGuard.tsx`, `theme-provider.tsx`, `client-orders.tsx`)

## Tempo real

`src/lib/echo.ts` configura Laravel Echo + Pusher-js (broadcaster `reverb`) — **uso real confirmado**, não é código morto:
- `src/hooks/use-realtime.ts` → `useRealtimeOrders`, consumido em `app/(dashboard)/orders/board/page.tsx`
- `src/hooks/use-realtime-dashboard.ts`, consumido em `dashboard/components/metrics-overview.tsx`

## Testes frontend

- Jest + React Testing Library. **Não há Vitest nem Playwright/e2e.**
- 11 arquivos em `src/__tests__/`: `cruds/` (orders, tasks, permissions, categories, users, clients, products, roles, index) e `permissions/` (2 arquivos), mais `test-utils.tsx` compartilhado

## Naming

- Kebab-case dominante para arquivos/pastas (`stat-cards.tsx`, `product-form-dialog.tsx`, `use-authenticated-api.ts`)
- PascalCase só para os nomes exportados de componente/tipo dentro dos arquivos, como de costume
- Única exceção de nome de arquivo em PascalCase: `PermissionGuard.tsx` — não copiar esse padrão

## O que não existe

Painel admin, múltiplos guards de autenticação, app mobile, módulos de Financeiro/Loyalty/Marketing, BFF dedicado ao dashboard.
