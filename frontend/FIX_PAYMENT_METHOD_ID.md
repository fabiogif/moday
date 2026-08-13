# 🔧 Correção: Campo payment_method_id Obrigatório

## 🚨 Erro Atual

```json
{
    "success": false,
    "message": "Erro de validação",
    "errors": {
        "payment_method_id": [
            "O campo payment method id é obrigatório."
        ]
    }
}
```

## 📋 Problema

O payload enviado pelo frontend **NÃO inclui** o campo `payment_method_id`:

```json
{
    "token_company": "a8c1788d-4c5e-49a7-9965-4afa8c026818",
    "client_id": "6d0afc36-507f-4653-829f-6d37f0b7ac6d",
    "table": "5c68688c-1a0d-4060-ac51-2752b11c4abe",
    "is_delivery": false,
    "products": [...],
    // ❌ Falta: payment_method_id
}
```

Mas o backend **EXIGE** este campo:

```php
// StoreOrderRequest.php (linha 35)
'payment_method_id' => ['required', 'string', 'exists:payment_methods,uuid'],
```

---

## ✅ Solução

### 1. Adicionar campo no Frontend

Você precisa adicionar `payment_method_id` ao payload do pedido.

#### Opção A: Adicionar seleção de forma de pagamento

Se ainda não há seleção de forma de pagamento no frontend:

```tsx
// No componente de checkout/carrinho
const [paymentMethodId, setPaymentMethodId] = useState<string>('')
const [paymentMethods, setPaymentMethods] = useState<PaymentMethod[]>([])

// Buscar formas de pagamento disponíveis
useEffect(() => {
  async function loadPaymentMethods() {
    const response = await apiClient.get(`/api/payment-method?token_company=${tenantUuid}`)
    if (response.success) {
      setPaymentMethods(response.data)
      // Selecionar a primeira por padrão
      if (response.data.length > 0) {
        setPaymentMethodId(response.data[0].uuid)
      }
    }
  }
  loadPaymentMethods()
}, [tenantUuid])

// Adicionar ao payload
const orderPayload = {
  token_company: tenantUuid,
  client_id: clientId,
  table: tableId,
  is_delivery: isDelivery,
  payment_method_id: paymentMethodId, // ✅ Adicionar este campo
  products: products.map(p => ({
    identify: p.uuid,
    qty: p.quantity,
    price: p.price
  }))
}
```

#### Opção B: Se já existe seleção, apenas adicionar ao payload

Procure no código onde o pedido é criado e adicione o campo:

```tsx
// Antes
const orderData = {
  token_company,
  client_id,
  table,
  is_delivery,
  products
}

// Depois
const orderData = {
  token_company,
  client_id,
  table,
  is_delivery,
  payment_method_id, // ✅ Adicionar
  products
}
```

---

## 🔍 Onde Fazer a Mudança

### Procure por arquivos relacionados a pedidos:

```bash
# No frontend, procure por:
src/app/store/[slug]/*
src/components/order/*
src/components/checkout/*
src/components/cart/*
```

### Busque por onde o payload é montado:

```tsx
// Procure por algo assim:
const payload = {
  token_company: ...,
  client_id: ...,
  // Adicione payment_method_id aqui
}

apiClient.post('/api/order', payload)
```

---

## 📊 Estrutura do Campo

### Tipo
```typescript
payment_method_id: string // UUID da forma de pagamento
```

### Exemplo de valor válido
```json
{
  "payment_method_id": "b2c4e6f8-1234-5678-90ab-cdef12345678"
}
```

### Como obter formas de pagamento disponíveis

**Endpoint**: `GET /api/payment-method?token_company={uuid}`

**Response**:
```json
{
  "success": true,
  "data": [
    {
      "uuid": "b2c4e6f8-1234-5678-90ab-cdef12345678",
      "name": "Dinheiro",
      "description": "Pagamento em dinheiro",
      "is_active": true
    },
    {
      "uuid": "c3d5e7f9-2345-6789-01bc-def123456789",
      "name": "Cartão de Crédito",
      "description": "Pagamento via cartão de crédito",
      "is_active": true
    }
  ]
}
```

---

## 🎯 Exemplo Completo de Implementação

### Componente de Seleção de Forma de Pagamento

```tsx
// components/payment-method-select.tsx
'use client'

import { useState, useEffect } from 'react'
import { apiClient } from '@/lib/api-client'
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'

interface PaymentMethod {
  uuid: string
  name: string
  description?: string
  is_active: boolean
}

interface PaymentMethodSelectProps {
  tenantUuid: string
  value: string
  onChange: (value: string) => void
}

export function PaymentMethodSelect({ 
  tenantUuid, 
  value, 
  onChange 
}: PaymentMethodSelectProps) {
  const [paymentMethods, setPaymentMethods] = useState<PaymentMethod[]>([])
  const [isLoading, setIsLoading] = useState(true)

  useEffect(() => {
    async function loadPaymentMethods() {
      try {
        const response = await apiClient.get(
          `/api/payment-method?token_company=${tenantUuid}`
        )
        
        if (response.success) {
          const activeMethods = response.data.filter(
            (pm: PaymentMethod) => pm.is_active
          )
          setPaymentMethods(activeMethods)
          
          // Selecionar o primeiro por padrão se não houver seleção
          if (!value && activeMethods.length > 0) {
            onChange(activeMethods[0].uuid)
          }
        }
      } catch (error) {
        console.error('Erro ao carregar formas de pagamento:', error)
      } finally {
        setIsLoading(false)
      }
    }

    if (tenantUuid) {
      loadPaymentMethods()
    }
  }, [tenantUuid])

  if (isLoading) {
    return <div>Carregando formas de pagamento...</div>
  }

  if (paymentMethods.length === 0) {
    return (
      <div className="text-sm text-red-500">
        Nenhuma forma de pagamento disponível
      </div>
    )
  }

  return (
    <Select value={value} onValueChange={onChange}>
      <SelectTrigger>
        <SelectValue placeholder="Selecione a forma de pagamento" />
      </SelectTrigger>
      <SelectContent>
        {paymentMethods.map((method) => (
          <SelectItem key={method.uuid} value={method.uuid}>
            {method.name}
            {method.description && (
              <span className="text-xs text-gray-500 ml-2">
                ({method.description})
              </span>
            )}
          </SelectItem>
        ))}
      </SelectContent>
    </Select>
  )
}
```

### Usando no Componente de Checkout

```tsx
// app/store/[slug]/checkout/page.tsx (exemplo)
'use client'

import { useState } from 'react'
import { PaymentMethodSelect } from '@/components/payment-method-select'
import { apiClient } from '@/lib/api-client'

export default function CheckoutPage() {
  const [paymentMethodId, setPaymentMethodId] = useState<string>('')
  const tenantUuid = 'a8c1788d-4c5e-49a7-9965-4afa8c026818'
  
  async function handleSubmitOrder() {
    // Validar se foi selecionado
    if (!paymentMethodId) {
      alert('Selecione uma forma de pagamento')
      return
    }
    
    const orderPayload = {
      token_company: tenantUuid,
      client_id: '6d0afc36-507f-4653-829f-6d37f0b7ac6d',
      table: '5c68688c-1a0d-4060-ac51-2752b11c4abe',
      is_delivery: false,
      use_client_address: false,
      payment_method_id: paymentMethodId, // ✅ Campo adicionado
      products: [
        {
          identify: '01dd0868-3d21-4e83-87cd-fb4d2d5b8d94',
          qty: 1,
          price: 6.5
        }
      ]
    }
    
    try {
      const response = await apiClient.post('/api/order', orderPayload)
      
      if (response.success) {
        alert('Pedido criado com sucesso!')
        // Redirecionar ou mostrar confirmação
      }
    } catch (error) {
      console.error('Erro ao criar pedido:', error)
    }
  }
  
  return (
    <div>
      <h1>Finalizar Pedido</h1>
      
      {/* Seleção de Forma de Pagamento */}
      <div className="mb-4">
        <label className="block text-sm font-medium mb-2">
          Forma de Pagamento *
        </label>
        <PaymentMethodSelect
          tenantUuid={tenantUuid}
          value={paymentMethodId}
          onChange={setPaymentMethodId}
        />
      </div>
      
      {/* Restante do formulário */}
      
      <button onClick={handleSubmitOrder}>
        Finalizar Pedido
      </button>
    </div>
  )
}
```

---

## 🔍 Validação Backend

O backend valida que:

1. ✅ Campo é **obrigatório** (`required`)
2. ✅ Valor é uma **string** (`string`)
3. ✅ UUID **existe** na tabela `payment_methods` (`exists:payment_methods,uuid`)
4. ✅ Forma de pagamento **pertence ao tenant** (validação customizada)
5. ✅ Forma de pagamento está **ativa** (`is_active = true`)

---

## 📋 Checklist de Implementação

Frontend:
- [ ] Criar componente de seleção de forma de pagamento
- [ ] Buscar formas de pagamento disponíveis do tenant
- [ ] Adicionar campo `payment_method_id` ao estado
- [ ] Adicionar seleção na UI do checkout/carrinho
- [ ] Incluir `payment_method_id` no payload do pedido
- [ ] Validar que campo foi preenchido antes de enviar
- [ ] Testar criação de pedido

Backend:
- [x] Validação de `payment_method_id` já existe
- [x] Validação de tenant scope já existe
- [x] Validação de ativo já existe

---

## 🧪 Teste

### Payload Correto

```json
{
    "token_company": "a8c1788d-4c5e-49a7-9965-4afa8c026818",
    "client_id": "6d0afc36-507f-4653-829f-6d37f0b7ac6d",
    "table": "5c68688c-1a0d-4060-ac51-2752b11c4abe",
    "is_delivery": false,
    "use_client_address": false,
    "payment_method_id": "UUID_DA_FORMA_DE_PAGAMENTO", // ✅ Adicionado
    "products": [
        {
            "identify": "01dd0868-3d21-4e83-87cd-fb4d2d5b8d94",
            "qty": 1,
            "price": 6.5
        }
    ]
}
```

### Response Esperada (Sucesso)

```json
{
    "success": true,
    "message": "Pedido criado com sucesso",
    "data": {
        "uuid": "...",
        "order_number": "12345",
        "total": 6.5,
        "status": "pending"
    }
}
```

---

**Status**: ✅ Problema identificado  
**Ação necessária**: Adicionar campo `payment_method_id` no frontend
