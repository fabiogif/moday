# Localização IBGE (Estados e Municípios)

## Visão geral

A aplicação mantém a base oficial do IBGE localmente no backend. Listagens de UF e municípios **não** chamam APIs externas em runtime.

| Recurso | Fonte |
|---------|--------|
| Estados | Tabela `states` (seed offline) |
| Municípios | Tabela `cities` (seed offline) |
| CEP | ViaCEP (proxy backend) + resolução na base local |

## Dados versionados

- `backend_moday/database/data/states.json` — 27 UFs com `ibge_code` e `region`
- `backend_moday/database/data/cities.json` — municípios com `ibge_code`, `state_ibge_code`, `is_capital`

O seeder `StatesAndCitiesSeeder` lê apenas esses arquivos (sem HTTP).

## APIs

| Método | Rota | Descrição |
|--------|------|-----------|
| GET | `/api/states` | Estados ordenados por nome (cache 24h) |
| GET | `/api/states/{id}/cities` | Municípios do estado (cache por state_id) |
| GET | `/api/cities/search?q=` | Busca por nome |
| GET | `/api/cep/{cep}` | Proxy ViaCEP + resolve `state`/`city` locais |

### Resolução de CEP

1. Backend consulta ViaCEP
2. Localiza município por `ibge` → `cities.ibge_code`
3. Fallback: UF + nome normalizado
4. Retorna logradouro/bairro + objetos `state` e `city` locais

## Frontend

- Hooks: `useStates`, `useCitiesByState` (`src/hooks/use-location.ts`)
- Componentes: `StateCityFormFields`, `StateCitySelect`
- CEP: `useViaCEP` → `/api/cep/{cep}`
- Helper: `applyCepToForm` / `applyCepToStateHandlers`

Valores persistidos nas entidades de negócio continuam sendo **UF (string)** e **nome da cidade (string)** — sem FK.

## Persistência de negócio

`clients`, `tenants`, `suppliers`, `orders` e zonas de entrega guardam endereço denormalizado. O catálogo IBGE é só para seleção e validação de UX.
