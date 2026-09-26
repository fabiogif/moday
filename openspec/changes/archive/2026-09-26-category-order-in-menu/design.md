# Design

## Context

See proposal.md - Why.
Atualmente, as categorias são modeladas em `Category` (`categories` table) e expostas via `CategoryApiController`, `CategoryRepository` e `PublicStoreService`. Na página `/store/[slug]`, os produtos carregados trazem categorias associadas, que são deduplicadas e ordenadas em memória via `.sort()` puramente alfabético.

## Goals / Non-Goals

**Goals:**
- Armazenar o campo `order` (inteiro não-negativo, default 0) na tabela `categories`.
- Permitir ao usuário visualizar e alterar a ordem no formulário e detalhes de categoria.
- Exibir a coluna "Ordem" na listagem de categorias (`DataTable`).
- Incluir `order` nas respostas da API (`CategoryResource`, `PublicStoreService`).
- Ordenar as seções do cardápio público primariamente por `order` ascendente e secundariamente por ordem alfabética.

**Non-Goals:**
- Implementar drag-and-drop na tabela neste momento.
- Alterar a ordenação padrão de outras tabelas ou módulos financeiros.

## Decisions

1. **Nome da coluna de ordenação:**
   - Decisão: Coluna `order` (integer, default 0) na tabela `categories`, com índice composto `['tenant_id', 'order']`.
   - Alternativa: `order_position` ou `sort_order`. Rejeitada pois o termo solicitado e mais direto é `order`, e o Eloquent do Laravel escapa nomes de colunas automaticamente em todos os drivers SQL.

2. **Ordenação no Cardápio Público (`/store/[slug]`):**
   - Decisão: Extrair as categorias dos produtos mapeando `{ name, order }`. Ordenar a lista única primariamente por `order` numérico ascendente, desempatando com `a.name.localeCompare(b.name, 'pt-BR')`. Produtos sem categoria vão para "Outros" no fim.
   - Alternativa: Endpoint separado só para categorias no cardápio público. Rejeitada para evitar overhead de rede desnecessário, mantendo o padrão já adotado de carregar os produtos com categorias no endpoint público existente.

3. **Validação no Backend:**
   - `StoreCategoryRequest`: `'order' => 'nullable|integer|min:0'`. Se não informado, assume 0.

## Risks / Trade-offs

- **Categorias legadas sem ordem explícita:**
  - Mitigação: Default 0 na migration. Categorias com mesmo valor de ordem usam ordenação alfabética como desempate, preservando a experiência existente até customização pelo usuário.
