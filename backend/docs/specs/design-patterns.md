# Design patterns — padrões em uso

Somente padrões **já presentes** no DistribTec.

## Camadas (Layered / “service–repository”)

Padrão dominante maduro:

`Controller → Service → RepositoryInterface → Eloquent Model`

Usar este fluxo em features novas de domínio Distribtec (Sale, Logistics, Stock, etc.).

## Repository + Interface (DIP)

- Contract em `app/Repositories/Contracts/`
- Bind no `*RepositoryServiceProvider` do domínio
- Service depende da interface, não da concrete

## Dependency Injection

- Container Laravel resolve controllers/services
- Frontend: React Context + hooks; não DI container

## Facade / Helper de resposta

- `ApiResponseClass` centraliza envelope JSON e rollback

## API Resource (Transform)

- Usado em CRUDs clássicos (`ProductResource`, `ClientResource`, …)
- Módulos novos podem devolver arrays — seguir o módulo

## Form Request

- Validação declarativa Laravel
- Coexiste com validate inline

## Strategy / Pipeline de reports

- `ReportBuilder` + `ReportExporterInterface` (Pdf/Excel/Csv)
- Seleção do exporter por formato em `ReportService`

## Adapter / Port (parcial)

- Email: `Ports/` + `Adapters/`
- Não exigir Ports para todo domínio novo

## Client externo encapsulado

- Ex.: `GoogleMapsRoutingClient`, `CosmosBarcodeClient`, `OpenFoodFactsBarcodeClient`
- Service de domínio orquestra; client só fala com API externa

## DTO pontual

- `BarcodeProductData`, DTOs Tenant
- Não criar pasta DTO por feature sem necessidade

## Frontend patterns

| Pattern | Onde |
|---------|------|
| Container page + presentational components | pages dashboard + `components/` |
| Custom hooks para data fetching | `useAuthenticatedApi`, `use-suppliers`, etc. |
| Controlled forms (RHF) | CRUDs |
| Provider composition | root + dashboard layouts |
| Route Handlers como BFF leve | `internal-api/image-search` |

## PDF

- View Blade + DomPDF (`loadHTML` após `view()->render()`)
- Service monta dados; controller streama

## O que não é padrão do projeto

- CQRS completo
- Event sourcing
- Clean Architecture estrita em todos os módulos
- `*ServiceInterface` obrigatório
- React Query / Redux como default
