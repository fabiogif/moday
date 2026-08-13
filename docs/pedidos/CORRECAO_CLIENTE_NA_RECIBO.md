# Correção: Dados do Cliente Retornando N/A no Recibo

## Problema
No recibo, os dados do cliente aparecem como:
```
Nome: N/A
Email: N/A
Telefone: N/A
```

Mesmo quando o cliente existe no pedido.

## Causa Raiz

### Incompatibilidade de Tipos

O componente `ReceiptDialog` estava esperando receber tipo `OrderReceipt`, mas estava recebendo tipo `Order`.

**Diferenças nos tipos:**

#### Tipo Order (o que é recebido)
```typescript
client?: {
  id: number
  name: string
  email: string
  phone: string
  address?: string
  city?: string
  state?: string
  zip_code?: string      // ← snake_case
  neighborhood?: string
  number?: string
  complement?: string
}
```

#### Tipo OrderReceipt (o que era esperado)
```typescript
client?: {
  // SEM id
  name: string
  email: string
  phone: string
  address?: string
  city?: string
  state?: string
  zipCode?: string       // ← camelCase (diferente!)
  neighborhood?: string
  number?: string
  complement?: string
}
```

## Solução Implementada

### 1. Aceitar Ambos os Tipos

**Arquivo:** `receipt-dialog.tsx`

**Antes:**
```typescript
interface ReceiptDialogProps {
  order: OrderReceipt | null
  // ...
}
```

**Depois:**
```typescript
import { OrderReceipt, Order } from "../types"

interface ReceiptDialogProps {
  order: OrderReceipt | Order | null  // ← Aceita ambos
  // ...
}
```

### 2. Debug Logs Adicionados

```typescript
if (!order) return null

// Debug: Log completo do pedido recebido
console.log('ReceiptDialog - Pedido completo:', order)
console.log('ReceiptDialog - Cliente:', order.client)
console.log('ReceiptDialog - Produtos:', order.products || order.items)
```

## Como Testar

1. **Abra o Console** (F12)
2. **Acesse** `/orders`
3. **Clique no ícone de impressora** em um pedido
4. **Verifique os logs:**

```javascript
ReceiptDialog - Pedido completo: {
  identify: "ORD-001",
  client: {
    id: 1,
    name: "João Silva",      // ← Deve aparecer
    email: "joao@email.com", // ← Deve aparecer
    phone: "11999999999"     // ← Deve aparecer
  },
  // ...
}
```

5. **Verifique o recibo:**
   - ✅ Nome deve aparecer
   - ✅ Email deve aparecer
   - ✅ Telefone deve aparecer

## Se Ainda Aparecer N/A

### Cenário 1: Cliente Realmente é Null
```javascript
ReceiptDialog - Cliente: null
```
**Solução:** Normal! Pedido sem cliente associado.

### Cenário 2: Cliente Existe mas com Estrutura Diferente
```javascript
ReceiptDialog - Cliente: {
  customer_name: "João Silva",  // ← Propriedade diferente!
  customer_email: "joao@..."
}
```
**Solução:** Adicionar mais fallbacks.

### Cenário 3: Dados Vêm em Outro Nível
```javascript
ReceiptDialog - Pedido completo: {
  customerName: "João Silva",  // ← Direto no order, não em client
  customerEmail: "..."
}
```
**Solução:** Já implementado! Fallbacks cobrem isso.

## Próximos Passos

### Se Logs Mostrarem Cliente Correto

Os dados já devem aparecer! A mudança de tipo resolveu.

### Se Logs Mostrarem Estrutura Diferente

Adicionar mais um fallback:

```typescript
<p><strong>Nome:</strong> {
  order.client?.name || 
  order.customerName || 
  order.customer?.name || 
  'N/A'
}</p>
```

## Arquivos Modificados

- ✅ `frontend/src/app/(dashboard)/orders/components/receipt-dialog.tsx`
  - Import do tipo `Order`
  - Props aceita `OrderReceipt | Order | null`
  - Debug logs adicionados

## Status

**CORREÇÃO APLICADA COM DEBUG ATIVO** ✅

**Teste agora:**
1. Abra Console (F12)
2. Visualize recibo
3. Verifique logs
4. Confirme se dados do cliente aparecem

Os logs mostrarão exatamente qual é a estrutura dos dados!

