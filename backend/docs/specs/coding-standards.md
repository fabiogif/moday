# Coding standards

Convenções observadas no código. Nomenclatura detalhada em `naming.md` (evitar duplicar aqui).

## Princípios aplicados no projeto

- **SRP**: controller fino; service orquestra; repository persiste (padrão maduro)
- **DRY**: reutilizar `ApiResponseClass`, `endpoints`, hooks, `components/ui`
- **KISS / YAGNI**: seguir o módulo vizinho; não criar abstração sem precedente

> Nota: SOLID/DRY são princípios de orientação do time sobre o padrão existente — não justificam inventar Clean Architecture onde o código não a usa.

## Backend

- Namespace `App\` PSR-4
- DI por constructor `private readonly` nos arquivos que já usam esse estilo
- Migrations em `database/migrations/`
- PDF: Blade em `resources/views/pdfs/`; DomPDF no service/exporter
- Respostas: `ApiResponseClass` (padrão predominante)
- Sem secrets no código (`.env`)

## Frontend

- TypeScript + alias `@/`
- `"use client"` nas pages dashboard com estado
- Forms: Zod + react-hook-form + shadcn `Form`
- API: `apiClient` + objeto `endpoints`
- Estado: Context/useState; Zustand só onde já existe

## Organização

- Backend por domínio em `Services/{Dominio}` quando o módulo vizinho já usa pasta
- Frontend: feature sob `app/(dashboard)/…/components/` ou `src/components/{domínio}/` se compartilhado

## Exceções conhecidas (não copiar em código novo)

- Eloquent/`DB` em alguns controllers
- `response()->json` manual misturado com `ApiResponseClass`
- Validação inline vs Form Request no mesmo projeto
