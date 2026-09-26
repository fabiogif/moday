# Tasks

## 1. Backend Database and Model

- [x] 1.1 Criar migration para adicionar coluna `order` (integer default 0) e índice `[tenant_id, order]` na tabela `categories`
- [x] 1.2 Atualizar Model `Category` com fillable e cast para `order`

## 2. Backend Request, Service, Repository and Resource

- [x] 2.1 Adicionar validação de `order` no `StoreCategoryRequest`
- [x] 2.2 Atualizar `CategoryService` para suportar `order` em store/update e reativação
- [x] 2.3 Atualizar `CategoryRepository` para ordenar por `order` asc e `name` asc
- [x] 2.4 Atualizar `CategoryResource` para retornar o campo `order`
- [x] 2.5 Atualizar `PublicStoreRepository` e `PublicStoreService` para incluir `order` nas categorias dos produtos

## 3. Frontend Menu Page and Types

- [x] 3.1 Atualizar tipos em `frontend/src/app/store/[slug]/menu-utils.ts` com `order?: number`
- [x] 3.2 Atualizar ordenação das seções em `frontend/src/app/store/[slug]/page.tsx` para ordenar por `order` asc com desempate alfabético
- [x] 3.3 Atualizar testes do cardápio público em `frontend/src/app/store/[slug]/__tests__/page.test.tsx`

## 4. Frontend Category Management Dashboard

- [x] 4.1 Atualizar `category-form-dialog.tsx` com schema e campo input para `order`
- [x] 4.2 Atualizar `data-table.tsx` adicionando coluna "Ordem" na listagem
- [x] 4.3 Atualizar `[id]/page.tsx` para exibir e permitir editar `order` nos detalhes da categoria
- [x] 4.4 Atualizar tipos na página principal de categorias `categories/page.tsx`
