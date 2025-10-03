# GUIA DE DEBUG: Cliente N/A no Recibo

## O Que Fazer AGORA

### Passo 1: Abrir Console
1. Pressione **F12** no navegador
2. Vá para a aba **Console**

### Passo 2: Visualizar Recibo
1. Acesse `/orders`
2. Clique no **ícone de impressora** em qualquer pedido

### Passo 3: Analisar Logs

Você verá logs assim:

```javascript
ReceiptDialog - Pedido completo: { ... }
ReceiptDialog - Cliente: { ... }
ReceiptDialog - Cliente JSON: "{ ... }"
ReceiptDialog - Todas as keys do pedido: ["identify", "total", ...]
ReceiptDialog - Tentando acessar: {
  order.client?.name: undefined,     // ← O QUE ESTÁ RETORNANDO?
  order.client?.email: undefined,
  order.client?.phone: undefined,
  order.customerName: "João Silva",  // ← PODE ESTAR AQUI!
  order.customer?.name: undefined
}
```

## O Que Procurar

### Cenário 1: Cliente está null
```javascript
ReceiptDialog - Cliente: null
```
**Significa:** Pedido sem cliente associado (normal para pedidos de balcão)

### Cenário 2: Cliente existe mas com estrutura diferente
```javascript
ReceiptDialog - Cliente: {
  nome: "João Silva",        // ← Português em vez de "name"
  email_cliente: "...",      // ← Nome diferente
  telefone: "..."            // ← Nome diferente
}
```
**Precisamos adicionar esses nomes aos fallbacks**

### Cenário 3: Cliente no nível superior
```javascript
ReceiptDialog - Pedido completo: {
  identify: "ORD-001",
  client_name: "João Silva",   // ← Direto no order
  client_email: "...",
  client: null                 // ← client é null mas dados estão fora
}
```

### Cenário 4: Cliente em propriedade diferente
```javascript
ReceiptDialog - Pedido completo: {
  customer: {                  // ← "customer" em vez de "client"
    name: "João Silva",
    email: "..."
  }
}
```

## Ações Baseadas nos Logs

### Se Ver "order.customerName: João Silva"
O cliente está no nível raiz! Já adicionei fallback para isso.

### Se Ver Nomes em Português
```javascript
ReceiptDialog - Cliente: {
  nome: "João Silva",
  email: "joao@email.com",
  telefone: "11999999999"
}
```

**COPIE E COLE AQUI** o log completo do cliente que vou ajustar os fallbacks.

### Se Ver Estrutura Aninhada Diferente
```javascript
ReceiptDialog - Cliente: {
  data: {
    name: "João Silva"
  }
}
```

**COPIE E COLE AQUI** para ajustarmos.

## Fallbacks Já Implementados

O código agora tenta:

### Para Nome:
1. `order.client?.name`
2. `order.customerName`
3. `order.customer?.name`
4. `order.client_name`

### Para Email:
1. `order.client?.email`
2. `order.customerEmail`
3. `order.customer?.email`
4. `order.client_email`

### Para Telefone:
1. `order.client?.phone`
2. `order.customerPhone`
3. `order.customer?.phone`
4. `order.client_phone`

## Teste Manual com Dados Reais

Copie o log completo do console e envie para análise:

```javascript
// COPIE TUDO QUE APARECER AQUI:
ReceiptDialog - Pedido completo: { ... TUDO ... }
ReceiptDialog - Cliente: { ... }
ReceiptDialog - Cliente JSON: " ... "
ReceiptDialog - Todas as keys do pedido: [ ... ]
ReceiptDialog - Tentando acessar: { ... }
```

## Próximos Passos

1. ✅ Execute os passos acima
2. ✅ Copie os logs do console
3. ✅ Envie os logs para análise
4. 🔄 Ajustaremos os fallbacks baseado na estrutura real

## Verificação do Backend

Se quiser verificar o que a API retorna diretamente:

```bash
# No terminal ou Postman
curl -H "Authorization: Bearer SEU_TOKEN" http://localhost/api/order
```

Isso mostra exatamente o que a API está enviando.

