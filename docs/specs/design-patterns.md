# Design patterns — padrões em uso

Somente padrões **já presentes** no Moday, verificados no código.

## Camadas (Layered / "service–repository")

Padrão dominante, único, sem variação por domínio:

`Controller → Service → RepositoryInterface → Eloquent Model → ApiResponseClass`

Não existe domínio com service-facade + sub-services — todo `*Service` é uma classe única. Não introduzir esse padrão sem que o domínio realmente cresça a esse ponto (YAGNI).

## Repository + Interface (DIP)

- Contract em `app/Repositories/Contracts/`
- Bind em `RepositoryServiceProvider` (`bootstrap/providers.php`) — **um único provider**, não há divisão por domínio
- Service depende da interface, não da concreta
- Paginação passa por `PaginateRepositoryInterface`/`PaginatePresenter`, não `LengthAwarePaginator` cru

## Dependency Injection

- Container Laravel resolve controllers/services
- Frontend: React Context + hooks; não há DI container

## Facade / Helper de resposta

- `ApiResponseClass` centraliza envelope JSON (`sendResponse`, `sendResponsePaginate`, `validationError`, `unauthorized`, `forbidden`, `rollback`, `throw`)
- `BaseController` é um wrapper fino opcional (`checkPermission()`); poucos controllers o estendem — não é obrigatório

## API Resource (Transform)

- Usado em CRUDs clássicos (16 classes em `app/Http/Resources/`)
- Módulos podem devolver arrays/models direto via `ApiResponseClass` — seguir o vizinho

## Form Request

- Validação declarativa Laravel — é o padrão **dominante** (42 classes)
- Coexiste com `validate()` inline em uma minoria de controllers

## Frontend patterns

| Pattern | Onde |
|---------|------|
| Container page + presentational components colocados | pages dashboard + `{area}/components/` (35 pastas) |
| Custom hook para data fetching | `useAuthenticatedApi` (`src/hooks/use-authenticated-api.ts`) — hook dominante, ~30 importadores |
| Controlled forms (RHF + Zod) | CRUDs (`*-form-dialog.tsx`) |
| Realtime via Echo | `useRealtimeOrders` (Kanban de pedidos), `use-realtime-dashboard.ts` (métricas do dashboard) |
| Drag and drop | `@dnd-kit/*` no Kanban de pedidos |
| Route Handlers como proxy leve | `src/app/api/*` — só para a landing page (auth/categories/orders/products/tables), não é BFF do dashboard |

Não existem hooks `useMutation`/`useMutationWithValidation` no código — não referenciá-los como padrão existente.

## O que não é padrão do projeto

- CQRS completo
- Event sourcing
- Clean Architecture estrita
- `*ServiceInterface` obrigatório
- Service-facade + sub-services (só citado aqui para dizer que **não existe** ainda)
- Hexagonal / Ports & Adapters
- Exceções de domínio customizadas (não há nenhuma em `app/Exceptions/` além do `Handler.php`)
- Pipeline de relatórios/PDF, integrações externas (iFood, email transacional) — nada disso existe no código atual
- React Query / Redux / Zustand como padrão de estado confirmado (Zustand está instalado mas sem uso identificado)
