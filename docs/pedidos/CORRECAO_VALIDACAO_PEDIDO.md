# ✅ Correção: Erros de Validação no Pedido

## 🔍 **Problemas Identificados**

**Erro do Backend:**
```json
{
    "success": false,
    "message": "Validation errors",
    "data": {
        "token_company": ["O campo token company é obrigatório."],
        "products.0.identify": ["O campo products.0.identify é obrigatório."],
        "products.0.qty": ["O campo products.0.qty é obrigatório."],
        "table": ["Mesa é obrigatória para pedidos que não são delivery."]
    }
}
```

### **Causas:**

1. **❌ token_company faltando** - Frontend não enviava
2. **❌ Estrutura de products incorreta** - Campo `identify` e `qty` incorretos
3. **❌ Mesa obrigatória** - Validação condicional não funcionando
4. **❌ Dados não mapeados** - Frontend enviava dados brutos do form

## ✅ **Soluções Implementadas**

### **1. Backend: StoreOrderRequest.php Melhorado**

```php
// ✅ ANTES: Validação incompleta
'products' => ['required'],
'products.*.identify'=> ['required', 'exists:products,uuid'],
'products.*.qty'=> ['required', 'integer', 'min:1'],

// ✅ DEPOIS: Validação completa
'products' => ['required', 'array'],                    // ← Especifica que é array
'products.*.identify'=> ['required', 'exists:products,uuid'],
'products.*.qty'=> ['required', 'integer', 'min:1'],
'products.*.price'=> ['nullable', 'numeric', 'min:0'], // ← Aceita preço

// ✅ ADICIONADO: Campo client_id
'client_id' => ['nullable', 'exists:clients,uuid'],     // ← Cliente opcional
```

### **2. Frontend: Mapeamento de Dados Correto**

```typescript
// ✅ ANTES: Enviava dados brutos do form
const result = await createOrder(endpoints.orders.create, 'POST', data);

// ✅ DEPOIS: Mapeia dados para formato do backend
const orderData = {
  token_company: tenantId,           // ← Obtém do usuário autenticado
  client_id: data.clientId || null,  // ← Cliente opcional
  table: data.isDelivery ? null : data.tableId,  // ← Lógica condicional
  is_delivery: data.isDelivery,
  // ... outros campos de delivery
  products: data.products.map(product => ({
    identify: product.productId,     // ← productId → identify
    qty: product.quantity,           // ← quantity → qty
    price: product.price             // ← price convertido
  }))
};

const result = await createOrder(endpoints.orders.create, 'POST', orderData);
```

### **3. Token Company Automático**

```typescript
// ✅ Obtém token da empresa do usuário autenticado
const auth = useAuth();
const user = auth.user;
const tenantId = user?.tenant?.uuid || user?.tenant_id;

if (!tenantId) {
  toast.error('Usuário não possui empresa associada');
  return;
}

const orderData = {
  token_company: tenantId,  // ← Sempre presente
  // ...
};
```

### **4. Produtos com Estrutura Correta**

```typescript
// ✅ Mapeamento dos produtos
products: data.products.map(product => ({
  identify: product.productId,  // ← Campo correto para o backend
  qty: product.quantity,        // ← Campo correto para o backend
  price: product.price          // ← Preço já convertido para number
}))

// ✅ Exemplo de resultado:
products: [
  {
    identify: "41fded39-f2bc-4b53-b2f6-93c72096ff16",
    qty: 2,
    price: 16.9
  }
]
```

### **5. Mesa Condicional**

```typescript
// ✅ Lógica condicional para mesa
table: data.isDelivery ? null : data.tableId,

// ✅ Resultado:
// - Se é delivery: table: null
// - Se não é delivery: table: "uuid-da-mesa"
```

## 🎯 **Estrutura Final dos Dados**

### **✅ Dados Enviados para o Backend**
```typescript
{
  token_company: "uuid-do-tenant",     // ← Obrigatório, obtido do usuário
  client_id: "uuid-do-cliente",        // ← Opcional
  table: null,                         // ← null se delivery, uuid se presencial
  is_delivery: true,                   // ← boolean
  use_client_address: false,           // ← boolean
  delivery_address: "Rua ABC, 123",    // ← string ou null
  delivery_city: "São Paulo",          // ← string ou null
  delivery_state: "SP",                // ← string ou null
  delivery_zip_code: "01234-567",      // ← string ou null
  delivery_neighborhood: "Centro",     // ← string ou null
  delivery_number: "123",              // ← string ou null
  delivery_complement: "Apto 45",      // ← string ou null
  delivery_notes: "Portão azul",       // ← string ou null
  comment: "",                         // ← string vazia
  products: [
    {
      identify: "41fded39-f2bc-4b53-b2f6-93c72096ff16",
      qty: 2,
      price: 16.9
    },
    {
      identify: "e07de885-6673-4491-8cc6-43609518bb6d",
      qty: 1,
      price: 22.5
    }
  ]
}
```

### **✅ Validações Atendidas**

| Campo | Status | Validação |
|-------|--------|-----------|
| `token_company` | ✅ | Presente, exists:tenants,uuid |
| `client_id` | ✅ | Opcional, exists:clients,uuid |
| `table` | ✅ | Condicional (null se delivery) |
| `products` | ✅ | Array obrigatório |
| `products.*.identify` | ✅ | UUID do produto |
| `products.*.qty` | ✅ | Quantidade integer ≥ 1 |
| `products.*.price` | ✅ | Preço numeric ≥ 0 |
| `is_delivery` | ✅ | Boolean |
| Campos delivery | ✅ | Strings opcionais |

## 🚀 **Como Testar**

### **1. Console Logs**
```
=== DADOS DO FORMULÁRIO ANTES DA CONVERSÃO ===
data original: {clientId: "...", products: [...], isDelivery: true, ...}

=== DADOS CONVERTIDOS PARA O BACKEND ===
orderData: {token_company: "...", products: [{identify: "...", qty: 2}]}
token_company: "uuid-tenant"
table: null
products: [{identify: "...", qty: 2, price: 16.9}]
```

### **2. Validação Passou**
- ✅ **Sem erro "token_company é obrigatório"**
- ✅ **Sem erro "products.0.identify é obrigatório"** 
- ✅ **Sem erro "products.0.qty é obrigatório"**
- ✅ **Sem erro "Mesa é obrigatória"** (se delivery)

### **3. Pedido Criado**
- ✅ **Toast:** "Pedido criado com sucesso!"
- ✅ **Redirecionamento** para `/orders`
- ✅ **Pedido salvo** no banco de dados

## 🔧 **Logs de Debug**

### **✅ Antes da Submissão**
```typescript
console.log('data original:', data);
console.log('token_company:', orderData.token_company);
console.log('table:', orderData.table);
console.log('products:', orderData.products);
```

### **✅ Campos Essenciais**
- **token_company:** UUID do tenant do usuário
- **products[].identify:** UUID do produto (não productId)
- **products[].qty:** Quantidade (não quantity)
- **table:** null se delivery, UUID se presencial

## 📋 **Checklist de Correções**

- ✅ **Backend:** StoreOrderRequest aceita client_id opcional
- ✅ **Backend:** products validado como array
- ✅ **Backend:** products.*.price aceito como numeric
- ✅ **Frontend:** useAuth importado e usado
- ✅ **Frontend:** token_company obtido do usuário
- ✅ **Frontend:** products mapeado corretamente
- ✅ **Frontend:** productId → identify
- ✅ **Frontend:** quantity → qty
- ✅ **Frontend:** table condicional (null se delivery)
- ✅ **Frontend:** client_id incluído
- ✅ **Frontend:** Logs de debug adicionados

---

**Status**: ✅ **CORREÇÕES IMPLEMENTADAS**
**Validação**: ✅ **Todos os campos obrigatórios atendidos**
**Mapeamento**: ✅ **Frontend → Backend funcionando**
**Resultado**: ✅ **Pedido deve ser criado com sucesso**