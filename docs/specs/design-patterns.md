# Design patterns — padrões em uso

Somente padrões **já presentes** no Moday (Alba Tec).

## Camadas (Layered / "service–repository")

Padrão dominante maduro:

`Controller → Service → RepositoryInterface → Eloquent Model`

Domínios grandes (Orders) intercalam um **service-facade** que delega para sub-services especializados antes de chegar ao repository:

`OrderApiController → OrderService (facade) → OrderCreationService/OrderQueryService/OrderWorkflowService/OrderLifecycleService → OrderRepositoryInterface → Order`

Usar o fluxo de camadas em features novas; só introduzir facade + sub-services quando o domínio já tiver a complexidade de Orders — não fragmentar services simples por precaução (YAGNI).

## Repository + Interface (DIP)

- Contract em `app/Repositories/Contracts/`
- Bind no `*RepositoryServiceProvider` do domínio (9 providers reais, ver `architecture.md`)
- Service depende da interface, não da concreta
- Paginação passa por `PaginateRepositoryInterface`/`PaginatePresenter`, não `LengthAwarePaginator` cru, quando o domínio já usa esse contrato

## Dependency Injection

- Container Laravel resolve controllers/services
- Frontend: React Context + hooks; não DI container

## Facade / Helper de resposta

- `ApiResponseClass` centraliza envelope JSON e rollback (`sendResponse`, `sendResponsePaginate`, `validationError`, `unauthorized`, `forbidden`, `conflict`, `rollback`, `throw`)
- `BaseController` é um wrapper fino opcional sobre `ApiResponseClass` — poucos controllers o estendem; não é obrigatório

## API Resource (Transform)

- Usado em CRUDs clássicos (`ProductResource`, `ClientResource`, `Integrations/Ifood/*Resource`, …)
- Módulos podem devolver arrays/models direto — seguir o módulo

## Form Request

- Validação declarativa Laravel — é o padrão **dominante** (108 classes)
- Coexiste com validate inline em poucos controllers (~12)

## Strategy / Pipeline de reports

- `ReportBuilderInterface` (`Builders/*`) + `ReportExporterInterface` (`Exporters/Pdf|Excel|Csv`)
- Seleção do exporter por formato em `ReportService::getExporter(string $format)`
- `Formatters/{CurrencyFormatter,DataFormatter}` para formatação de saída

## Adapter / Port (parcial)

- iFood: `Ports/Integrations/Ifood/*` (interfaces) + `Adapters/Integrations/Ifood/Http/*` (implementação via `Http` facade)
- Email: `Adapters/Email/*` (`EmailAdapterInterface` + `EmailAdapterFactory` + Mailchimp/Postmark/Ses/Smtp)
- Não exigir Ports para todo domínio novo — é um padrão usado só nessas duas integrações

## DTO pontual

- `CreateTenantDTO`/`UpdateTenantDTO`, DTOs de iFood (`IfoodOrderDTO`, etc.), todos `readonly` com constructor promotion
- Não criar pasta DTO por feature sem necessidade — a maioria dos domínios passa arrays validados direto

## Facade de service (novo, específico do Orders)

- `OrderService` não contém lógica própria — só delega para `OrderCreationService`/`OrderQueryService`/`OrderWorkflowService`/`OrderLifecycleService`
- Usar esse padrão quando um domínio crescer a ponto de um único `*Service` ficar difícil de navegar; não é o padrão default de todo módulo novo

## Frontend patterns

| Pattern | Onde |
|---------|------|
| Container page + presentational components colocados | pages dashboard + `{area}/components/` |
| Custom hooks para data fetching | `useAuthenticatedApi`, `useMutation`, `useMutationWithValidation` |
| Controlled forms (RHF + Zod) | CRUDs (`*-form-dialog.tsx`) |
| Provider composition | `RootLayout` (Auth) + `(dashboard)/layout` (AuthGuard → Notifications → POSHeader → Sidebar) |
| Realtime via Echo | `useRealtimeOrders`, hoje só no Kanban (`orders/board`) |
| Drag and drop | `@dnd-kit/core` no Kanban de pedidos |
| Route Handlers como proxy leve | `src/app/api/*` — só para a landing page (contato/newsletter/analytics), não é BFF do dashboard |

## PDF

- `PdfExporter` monta HTML por concatenação de string e chama `Pdf::loadHTML($html)` — **não** existe view Blade para PDF neste projeto; não introduzir esse padrão sem necessidade real
- Service (`ReportService`) monta dados; exporter gera o arquivo

## O que não é padrão do projeto

- CQRS completo
- Event sourcing
- Clean Architecture estrita em todos os módulos
- `*ServiceInterface` obrigatório
- React Query / Redux como default
- Exceções de domínio customizadas (usa `\DomainException` genérica)
- Views Blade para PDF
