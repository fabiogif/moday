# Como Adicionar Atualização Automática na Página de Edição de Pedidos

Este guia complementa o arquivo `ATUALIZACAO_GRID_PEDIDOS.md` e mostra como aplicar a mesma funcionalidade na página de edição de pedidos.

## Arquivo: `frontend/src/app/(dashboard)/orders/edit/[id]/page.tsx`

### Passo 1: Importar o Hook

No início do arquivo, adicione o import do hook:

```typescript
import { useOrderRefresh } from "@/hooks/use-order-refresh"
```

### Passo 2: Usar o Hook no Componente

Dentro do componente, adicione:

```typescript
export default function EditOrderPage({ params }: { params: { id: string } }) {
  const router = useRouter()
  const { triggerRefresh } = useOrderRefresh() // <-- Adicionar esta linha
  
  // ... resto do código
}
```

### Passo 3: Chamar após Atualização Bem-Sucedida

Localize onde o pedido é atualizado (geralmente dentro de `onSubmit` ou `handleUpdateOrder`) e adicione `triggerRefresh()` após o sucesso:

```typescript
const handleUpdateOrder = async (data: OrderFormValues) => {
  try {
    const result = await updateOrder(endpoints.orders.update(params.id), 'PUT', data)
    
    if (result) {
      toast.success('Pedido atualizado com sucesso!')
      triggerRefresh() // <-- Adicionar esta linha
      router.push('/orders')
    }
  } catch (error: any) {
    console.error('Erro ao atualizar pedido:', error)
    toast.error(error.message || 'Erro ao atualizar pedido')
  }
}
```

## Exemplo Completo

```typescript
"use client"

import { useRouter } from "next/navigation"
import { toast } from "sonner"
import { useOrderRefresh } from "@/hooks/use-order-refresh" // Import adicionado
import { useMutation } from "@/hooks/use-api"
import { endpoints } from "@/lib/api-client"

export default function EditOrderPage({ params }: { params: { id: string } }) {
  const router = useRouter()
  const { triggerRefresh } = useOrderRefresh() // Hook adicionado
  const { mutate: updateOrder, loading: updating } = useMutation()

  const onSubmit = async (data: OrderFormValues) => {
    try {
      const result = await updateOrder(
        endpoints.orders.update(params.id), 
        'PUT', 
        data
      )
      
      if (result) {
        toast.success('Pedido atualizado com sucesso!')
        triggerRefresh() // Notifica a lista para atualizar
        router.push('/orders')
      }
    } catch (error: any) {
      console.error('Erro ao atualizar pedido:', error)
      toast.error(error.message || 'Erro ao atualizar pedido')
    }
  }

  return (
    // ... JSX do formulário
  )
}
```

## Verificação

Após implementar:

1. Edite um pedido existente
2. Salve as alterações
3. Ao retornar para `/orders`, a lista deve mostrar as mudanças automaticamente
4. Não é necessário dar refresh (F5) na página

## Funciona Para Qualquer Operação

O mesmo padrão pode ser aplicado para:

- ✅ Criar pedido (já implementado)
- ✅ Editar pedido (instruções acima)
- ✅ Excluir pedido (já usa `refetch()` local)
- ✅ Faturar pedido (já implementado)
- ✅ Qualquer outra operação que modifique pedidos

## Padrão Reutilizável

Este mesmo padrão pode ser replicado para outras entidades:

### Produtos
```typescript
// frontend/src/hooks/use-product-refresh.ts
import { create } from 'zustand'

interface ProductRefreshStore {
  shouldRefresh: boolean
  triggerRefresh: () => void
  resetRefresh: () => void
}

export const useProductRefresh = create<ProductRefreshStore>((set) => ({
  shouldRefresh: false,
  triggerRefresh: () => set({ shouldRefresh: true }),
  resetRefresh: () => set({ shouldRefresh: false }),
}))
```

### Clientes
```typescript
// frontend/src/hooks/use-client-refresh.ts
import { create } from 'zustand'

interface ClientRefreshStore {
  shouldRefresh: boolean
  triggerRefresh: () => void
  resetRefresh: () => void
}

export const useClientRefresh = create<ClientRefreshStore>((set) => ({
  shouldRefresh: false,
  triggerRefresh: () => set({ shouldRefresh: true }),
  resetRefresh: () => set({ shouldRefresh: false }),
}))
```

Basta criar o hook e seguir os mesmos 3 passos:
1. Importar
2. Usar no componente
3. Chamar `triggerRefresh()` após operação bem-sucedida

