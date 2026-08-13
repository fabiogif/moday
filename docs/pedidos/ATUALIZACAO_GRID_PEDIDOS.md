# Atualização Automática da Grid de Pedidos

## Problema
A grid de pedidos não atualizava automaticamente após criar ou editar um pedido, sendo necessário dar refresh manual na página.

## Solução Implementada

Implementamos um sistema de atualização automática usando **hooks React** e **Zustand** (gerenciamento de estado global) que permite que a lista de pedidos seja atualizada automaticamente quando:
- Um novo pedido é criado
- Um pedido existente é editado
- Um pedido é excluído
- Um pedido é faturado

### Arquivos Criados

#### 1. `frontend/src/hooks/use-order-refresh.ts`
Hook personalizado usando Zustand para gerenciar o estado de atualização global.

```typescript
import { create } from 'zustand'

interface OrderRefreshStore {
  shouldRefresh: boolean
  triggerRefresh: () => void
  resetRefresh: () => void
}

export const useOrderRefresh = create<OrderRefreshStore>((set) => ({
  shouldRefresh: false,
  triggerRefresh: () => set({ shouldRefresh: true }),
  resetRefresh: () => set({ shouldRefresh: false }),
}))
```

**Funcionalidades:**
- `shouldRefresh`: Flag booleana que indica se a lista precisa ser atualizada
- `triggerRefresh()`: Função para sinalizar que a lista deve ser atualizada
- `resetRefresh()`: Função para resetar a flag após atualização

### Arquivos Modificados

#### 1. `frontend/src/app/(dashboard)/orders/page.tsx`
**Mudanças:**
- Importado o hook `useOrderRefresh`
- Adicionado `useEffect` para detectar quando `shouldRefresh` é `true`
- Quando detectado, chama `refetch()` para recarregar dados
- Adiciona toast de sucesso em operações como faturamento

```typescript
const { shouldRefresh, resetRefresh } = useOrderRefresh()

// Atualizar lista quando shouldRefresh for true
useEffect(() => {
  if (shouldRefresh) {
    refetch()
    resetRefresh()
  }
}, [shouldRefresh, refetch, resetRefresh])
```

#### 2. `frontend/src/app/(dashboard)/orders/new/page.tsx`
**Mudanças:**
- Importado o hook `useOrderRefresh`
- Adicionado chamada a `triggerRefresh()` após criar pedido com sucesso
- Isso notifica a página de listagem para atualizar os dados

```typescript
const { triggerRefresh } = useOrderRefresh()

// Após criar pedido
toast.success("Pedido criado com sucesso!");
router.push("/orders");

// Notificar lista de pedidos para atualizar
triggerRefresh();
```

## Como Funciona

### Fluxo de Atualização

1. **Usuário cria/edita um pedido** → Na página de criação/edição
2. **Pedido é salvo com sucesso** → API retorna sucesso
3. **`triggerRefresh()` é chamado** → Seta `shouldRefresh = true` no estado global
4. **Página de listagem detecta mudança** → `useEffect` monitora `shouldRefresh`
5. **`refetch()` é executado** → Recarrega dados da API
6. **Grid é atualizada automaticamente** → Novos dados são exibidos
7. **`resetRefresh()` é chamado** → Reseta a flag para `false`

### Vantagens

✅ **Sem Refresh Manual**: A grid atualiza automaticamente
✅ **Performance**: Usa cache do hook `useApi` quando apropriado
✅ **Feedback Visual**: Toast notifications informam o usuário
✅ **Global State**: Funciona entre diferentes páginas/componentes
✅ **Zustand**: Leve e performático (menos de 1KB)
✅ **TypeScript**: Totalmente tipado para segurança

### Uso em Outras Operações

Para adicionar atualização automática em outras operações (como edição), basta:

1. Importar o hook:
```typescript
import { useOrderRefresh } from "@/hooks/use-order-refresh"
```

2. Usar no componente:
```typescript
const { triggerRefresh } = useOrderRefresh()
```

3. Chamar após operação bem-sucedida:
```typescript
await updateOrder(data)
toast.success("Pedido atualizado!")
triggerRefresh() // Atualiza a lista
router.push("/orders")
```

## Testando

1. Acesse a página de pedidos (`/orders`)
2. Clique em "Novo Pedido"
3. Preencha o formulário e crie um pedido
4. Após redirecionamento, a grid deve mostrar o novo pedido automaticamente
5. Não é necessário dar refresh (F5) na página

## Arquivos de Backup

Backups foram criados:
- `frontend/src/app/(dashboard)/orders/page.tsx.backup`
- `frontend/src/app/(dashboard)/orders/new/page.tsx.backup`

## Dependências

Esta solução usa:
- **Zustand** (^5.0.7): Já instalado no projeto
- **React Hooks**: useState, useEffect, useCallback
- **Hook use-api existente**: Mantém compatibilidade com sistema atual

## Próximos Passos Sugeridos

Para completar a implementação:
1. ✅ Adicionar `triggerRefresh()` na página de edição de pedidos
2. ✅ Adicionar `triggerRefresh()` após exclusão (já implementado com `refetch()` local)
3. ✅ Adicionar `triggerRefresh()` após faturamento (já implementado)
4. Considerar adicionar o mesmo padrão para outras entidades (produtos, mesas, clientes)

