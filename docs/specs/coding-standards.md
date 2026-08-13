# Coding standards

Convenções observadas no código. Nomenclatura detalhada em `naming.md` (evitar duplicar aqui).

## Princípios aplicados no projeto

- **SRP**: controller fino; service (ou service-facade + sub-services em domínios grandes como Orders) orquestra; repository persiste
- **DRY**: reutilizar `ApiResponseClass`, `endpoints`, hooks (`useAuthenticatedApi`/`useMutation`), `components/ui`
- **KISS / YAGNI**: seguir o módulo vizinho; não criar abstração sem precedente (não fragmentar em sub-services um domínio simples só porque Orders faz isso)

> Nota: SOLID/DRY são princípios de orientação do time sobre o padrão existente — não justificam inventar Clean Architecture ou hierarquia de exceptions onde o código não a usa.

## Backend

- Namespace `App\` PSR-4
- DI por constructor com property promotion
- Migrations em `database/migrations/`
- PDF: HTML montado em `Reports/Exporters/PdfExporter` (sem Blade) + DomPDF
- Respostas: `ApiResponseClass` (padrão predominante; `BaseController` é wrapper opcional, não obrigatório)
- Erros de domínio: `\DomainException` genérica, sem exceptions customizadas
- Sem secrets no código (`.env`)

## Frontend

- TypeScript + alias `@/`
- `"use client"` nas pages dashboard com estado
- Forms: Zod + react-hook-form + shadcn `Form`
- API: `apiClient` + objeto `endpoints` (nunca `use-api.ts`, que é legado morto)
- Estado: Context/useState; Zustand só onde já existe (chat, mail, order-refresh)

## Organização

- Backend: services flat em `Services/`; subpastas só para integrações (`Services/Integrations/Ifood/`) ou domínios com sub-services (Orders)
- Frontend: feature sob `app/(dashboard)/…/components/` (padrão dominante) ou `src/components/{domínio}/` só se já for cross-cutting

## Exceções conhecidas (não copiar em código novo)

- Eloquent/`DB` em alguns controllers
- `response()->json` manual misturado com `ApiResponseClass`
- Validação inline vs Form Request no mesmo projeto (Form Request é o padrão a seguir)
- `src/hooks/use-api.ts` no frontend — legado sem uso real, não referenciar em código novo
