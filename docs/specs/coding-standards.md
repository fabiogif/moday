# Coding standards

Convenções observadas no código. Nomenclatura detalhada em `naming.md` (evitar duplicar aqui).

## Princípios aplicados no projeto

- **SRP**: controller fino; service orquestra; repository persiste (sem facade/sub-services em nenhum domínio hoje)
- **DRY**: reutilizar `ApiResponseClass`, `use-authenticated-api.ts`, `components/ui`
- **KISS / YAGNI**: seguir o módulo vizinho; não criar abstração sem precedente

> Nota: SOLID/DRY são princípios de orientação do time sobre o padrão existente — não justificam inventar Clean Architecture ou hierarquia de exceptions onde o código não a usa.

## Backend

- Namespace `App\` PSR-4
- DI por constructor com property promotion
- Migrations em `database/migrations/`
- Respostas: `ApiResponseClass` (padrão predominante; `BaseController` é wrapper opcional, não obrigatório)
- Erros: capturar `\Exception`/exceção específica do framework no controller; não há hierarquia de exceptions de domínio
- Sem secrets no código (`.env`)

## Frontend

- TypeScript + alias `@/`
- `"use client"` nas pages dashboard com estado
- Forms: Zod + react-hook-form + shadcn `Form`
- API: `ApiClient` (`src/lib/api-client.ts`) via `use-authenticated-api.ts` — nunca `use-api.ts`, que é legado em migração

## Organização

- Backend: services e repositories **flat** em `Services/`/`Repositories/`, sem subpastas por domínio
- Frontend: feature sob `app/(dashboard)/…/components/` (padrão dominante) ou `src/components/{domínio}/` só se já for cross-cutting

## Exceções conhecidas (não copiar em código novo)

- Validação inline vs Form Request no mesmo projeto (Form Request é o padrão a seguir)
- `src/hooks/use-api.ts` (+ `use-api.ts.backup`) no frontend — legado em migração, quase sem uso real
- `app/(dashboard)/dashboard-old/` — rota duplicada/obsoleta, não usar como referência
- `throttle:write` referenciado em rotas sem `RateLimiter::for('write')` correspondente — não copiar throttle sem definir o limiter primeiro
