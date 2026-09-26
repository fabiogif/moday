# Proposal

## Why

Atualmente, as categorias exibidas na página pública do cardápio (`/store/[slug]`) são ordenadas estritamente por ordem alfabética. Os lojistas precisam ter controle sobre a ordem de exibição das categorias no cardápio (por exemplo, destacar "Pizzas" ou "Entradas" antes de "Bebidas" e "Sobremesas"), definindo uma ordem numérica explícita no módulo de categorias.

## What Changes

- Adição do atributo `order` (inteiro $\ge 0$, padrão 0) à entidade de Categoria no banco de dados e APIs.
- Disponibilização do campo de ordem de exibição nos formulários de criação/edição de categoria (`CategoryFormDialog` e `CategoryDetailPage`).
- Exibição da coluna de ordem na listagem de categorias do painel administrativo (`DataTable`).
- Atualização do recurso de categorias e do payload de produtos do cardápio público (`PublicStoreService`) para incluir a informação de `order` de cada categoria.
- Atualização da página pública do cardápio (`/store/[slug]`) para ordenar as seções de categoria e as abas de navegação primariamente pelo campo `order` ascendente, utilizando o nome alfabético como critério de desempate.

## Capabilities

### Modified Capabilities
- `public-store-menu`: Atualização da especificação de ordenação das seções de categoria para refletir a ordem definida na categoria (`order` ascendente, depois alfabética por nome).
