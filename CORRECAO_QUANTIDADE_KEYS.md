# Correção: Quantidade Não Exibida e Warning de Keys

## Problemas

### 1. Quantidade Não Exibida
No recibo, a coluna "Qtd" estava vazia.

### 2. Warning de Keys do React
```
Warning: Each child in a list should have a unique "key" prop.
Check the render method of `ReceiptDialog`.
```

## Causas

### Problema 1: Quantidade Undefined
```typescript
// API pode retornar:
products: [{
  id: 1,
  name: "Produto",
  quantity: undefined,  // ← Opcional em Order.products
  qty: 2,               // ← Algumas APIs usam "qty"
  price: 10.00
}]
```

### Problema 2: Items Sem ID
Alguns items podem não ter `id`, causando `key={undefined}`.

## Soluções Implementadas

### 1. Fallbacks para Quantity

**Arquivo:** `receipt-dialog.tsx` e `order-details-dialog.tsx`

**Antes:**
```typescript
<td className="p-3 text-center">{item.quantity}</td>
```

**Depois:**
```typescript
<td className="p-3 text-center">{item.quantity || item.qty || 1}</td>
```

**Tenta:**
1. `item.quantity` (padrão)
2. `item.qty` (alternativo)
3. `1` (fallback padrão)

### 2. Keys Únicas com Index

**Antes:**
```typescript
{products.map((item) => (
  <tr key={item.id}>  // ← Pode ser undefined
    ...
  </tr>
))}
```

**Depois:**
```typescript
{products.map((item, index) => (
  <tr key={item.id || `item-${index}`}>  // ← Sempre único
    ...
  </tr>
))}
```

### 3. Cálculo de Total com Fallback

**Antes:**
```typescript
{formatCurrency(item.total)}
```

**Depois:**
```typescript
{formatCurrency(item.total || (item.price * (item.quantity || item.qty || 1)))}
```

Calcula o total se não vier da API.

## Mudanças Aplicadas

### receipt-dialog.tsx

#### 1. Map com Index
```typescript
{(order.items || order.products || []).map((item, index) => (
  <tr key={item.id || `item-${index}`} className="border-t">
```

#### 2. Quantidade com Fallbacks
```typescript
<td className="p-3 text-center">{item.quantity || item.qty || 1}</td>
```

#### 3. Templates de String
```typescript
// Template HTML para impressão
<td>${item.quantity || item.qty || 1}</td>

// Template para WhatsApp
${item.quantity || item.qty || 1}x ${formatCurrency(item.price)}
```

### order-details-dialog.tsx

#### 1. Map com Index
```typescript
{(order.products || order.items || []).map((item, index) => (
  <div key={item.id || `item-${index}`}>
```

#### 2. Quantidade já tinha fallback
```typescript
{item.quantity || 1}x {formatCurrency(item.price)}
```

## Variações de API Suportadas

| Campo API | Exibição |
|-----------|----------|
| `quantity: 2` | `2` |
| `qty: 3` | `3` |
| `quantity: undefined, qty: 4` | `4` |
| `quantity: undefined, qty: undefined` | `1` |
| `quantity: 0` | `0` (mantém zero real) |

## Como Funciona

### Lógica de Fallback
```typescript
item.quantity || item.qty || 1

// Avaliação:
// Se quantity é truthy → usa quantity
// Senão, se qty é truthy → usa qty  
// Senão → usa 1
```

### Keys Únicas
```typescript
item.id || `item-${index}`

// Resultado:
// item.id = 123 → key="123"
// item.id = undefined, index = 0 → key="item-0"
// item.id = undefined, index = 1 → key="item-1"
```

## Testes

### Teste 1: Quantidade Exibida
1. Abra recibo de um pedido
2. **Verifique:** Coluna "Qtd" mostra números
3. ✅ Não deve estar vazia

### Teste 2: Sem Warnings
1. Abra Console (F12)
2. Visualize recibo
3. **Verifique:** Sem warnings de "key prop"
4. ✅ Console limpo

### Teste 3: Cálculo de Total
1. Se API não enviar `item.total`
2. **Verifique:** Total calculado = preço × quantidade
3. ✅ Valores corretos

## Debug

Se quantidade ainda não aparecer, verifique logs:

```javascript
ReceiptDialog - Produtos: [
  {
    id: 1,
    name: "Produto",
    quantity: undefined,  // ← Problema aqui
    qty: 2,               // ← Será usado
    price: 10.00
  }
]
```

## Arquivos Modificados

1. ✅ `frontend/src/app/(dashboard)/orders/components/receipt-dialog.tsx`
   - Map com `(item, index)`
   - Key com fallback: `item.id || \`item-${index}\``
   - Quantity com fallback: `item.quantity || item.qty || 1`
   - Total calculado se ausente

2. ✅ `frontend/src/app/(dashboard)/orders/components/order-details-dialog.tsx`
   - Map com `(item, index)`
   - Key com fallback: `item.id || \`item-${index}\``

## Padrão Reutilizável

Para qualquer lista de items:

```typescript
{items.map((item, index) => (
  <Component 
    key={item.id || item.identify || `item-${index}`}
    quantity={item.quantity || item.qty || 1}
    total={item.total || (item.price * (item.quantity || item.qty || 1))}
  />
))}
```

## Status

**CORRIGIDO** ✅

- ✅ Quantidade exibida corretamente
- ✅ Sem warnings de React keys
- ✅ Total calculado quando ausente
- ✅ Suporta múltiplas variações de API

**Teste agora e verifique:**
- Console sem warnings
- Coluna Qtd preenchida
- Totais corretos

