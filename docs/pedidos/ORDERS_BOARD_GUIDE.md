# Guia de Uso - Quadro de Pedidos Refatorado

## 🎯 O que foi corrigido

### ✅ Drag and Drop funcionando
- Cards agora podem ser arrastados entre colunas
- Feedback visual durante o arraste
- IDs únicos garantem funcionamento correto

### ✅ Status de Conexão correto
- Badge mostra "Online" quando WebSocket conectado
- Badge mostra "Offline" quando WebSocket desconectado
- Ícone Wifi/WifiOff correspondente

### ✅ Dados da API mapeados corretamente
- Suporte completo para estrutura da API
- Exibição de cliente, mesa e produtos
- Cálculo correto de totais

---

## 🔧 Como Usar

### Estrutura de Dados da API

O componente espera dados no seguinte formato:

```json
{
  "data": [
    {
      "identify": "2iqpg6j8",
      "total": "6.00",
      "client": {
        "id": 13,
        "name": "Nome do Cliente",
        "email": "email@example.com",
        "phone": "(11) 99999-9999"
      },
      "table": {
        "id": 1,
        "identify": "MESA-001",
        "name": "Mesa Principal",
        "capacity": "4"
      },
      "status": "Em Preparo",
      "date": "05/10/2025 12:39:09",
      "created_at": "05/10/2025 12:39:09",
      "products": [
        {
          "identify": "39ef0065-d98a-4378-8e26-9cbd9d2bc1cb",
          "name": "Suco de Laranja 300ml",
          "price": "6.00",
          "quantity": 1
        }
      ],
      "is_delivery": false,
      "full_delivery_address": null,
      "delivery_notes": null,
      "comment": ""
    }
  ],
  "success": true
}
```

---

## 🎨 Componentes do Quadro

### 1. Card do Pedido (OrderCard)

Exibe as informações do pedido:
- **Cabeçalho**: `#identify` e badge de status
- **Cliente**: 👤 Nome do cliente
- **Mesa**: 🪑 Nome da mesa (quando não é delivery)
- **Delivery**: 🚚 Endereço completo + observações
- **Produtos**: Lista com até 3 produtos (+ indicador se houver mais)
- **Total**: Valor formatado em R$

**Funcionalidade de Drag:**
- Cursor muda para `grab` ao passar o mouse
- Cursor muda para `grabbing` ao arrastar
- Card fica semi-transparente durante o arraste
- DragOverlay mostra "fantasma" do card

### 2. Área Droppable (DroppableColumnArea)

Área onde os cards são soltos:
- **Normal**: Fundo padrão
- **Hover durante drag**: Fundo accent + borda tracejada azul
- **Min-height**: 200px para facilitar drop em colunas vazias

### 3. Coluna (BoardColumn)

Estrutura de cada coluna:
- **Header**: Título + Badge com contagem de pedidos
- **Content**: Área droppable com cards
- **Indicador**: "Atualizando..." quando salvando

### 4. Página Principal (OrdersBoardPage)

Gerencia todo o estado e lógica:
- **Loading**: Mostra spinner ao carregar
- **Conexão**: Badge Online/Offline
- **Atualizar**: Botão com ícone que gira ao recarregar
- **Real-time**: Atualização automática via WebSocket

---

## 🚀 Fluxo de Drag and Drop

### Passo 1: Drag Start
```
Usuário clica e segura no card
  ↓
handleDragStart é chamado
  ↓
activeOrder é definido
  ↓
DragOverlay mostra "fantasma" do card
```

### Passo 2: Drag Over
```
Usuário move o mouse sobre uma coluna
  ↓
Coluna alvo fica destacada (fundo + borda)
  ↓
Feedback visual indica onde o card será solto
```

### Passo 3: Drag End
```
Usuário solta o card
  ↓
handleDragEnd é chamado
  ↓
Identifica coluna de destino
  ↓
Valida se status é diferente
  ↓
Chama API: PUT /orders/{identify} { status: "Pronto" }
  ↓
Atualiza estado local (otimista)
  ↓
Mostra toast de sucesso
  ↓
activeOrder = null (remove overlay)
```

---

## 🔌 WebSocket Real-time

### Eventos Suportados

#### 1. Order Created
```typescript
onOrderCreated: (newOrder) => {
  // Adiciona novo pedido no início da lista
  // Evita duplicatas
  // Mostra toast de sucesso
}
```

#### 2. Order Status Updated
```typescript
onOrderStatusUpdated: ({ order, oldStatus, newStatus }) => {
  // Move card para nova coluna
  // Mantém outros dados do pedido
  // Mostra toast informativo
}
```

#### 3. Order Updated
```typescript
onOrderUpdated: (updatedOrder) => {
  // Atualiza dados do pedido
  // Mantém status atual
  // Atualização silenciosa (sem toast)
}
```

### Badge de Status

```
Online  (Verde + Wifi)     = WebSocket conectado e funcionando
Offline (Cinza + WifiOff)  = WebSocket desconectado ou indisponível
```

**Importante:** Mesmo offline, o drag and drop continua funcionando normalmente!

---

## 📋 Status Disponíveis

| Status | Cor | Descrição |
|--------|-----|-----------|
| **Em Preparo** | 🟨 Amarelo | Pedido sendo preparado |
| **Pronto** | 🟦 Azul | Pedido pronto para entrega |
| **Entregue** | 🟩 Verde | Pedido entregue ao cliente |
| **Cancelado** | 🟥 Vermelho | Pedido cancelado |

---

## 🛠️ API Endpoints

### GET /orders
Retorna lista de pedidos:
```typescript
const res = await apiClient.get(endpoints.orders.list)
// res.data ou res.data.data contém array de pedidos
```

### PUT /orders/{identify}
Atualiza status do pedido:
```typescript
await apiClient.put(
  endpoints.orders.update(orderIdentify), 
  { status: newStatus }
)
```

---

## 🎯 Casos de Uso

### 1. Arrastar pedido de "Em Preparo" para "Pronto"
```
1. Usuário segura card do pedido #2iqpg6j8
2. Arrasta sobre a coluna "Pronto"
3. Coluna fica destacada (feedback visual)
4. Usuário solta o card
5. API é chamada: PUT /orders/2iqpg6j8 { status: "Pronto" }
6. Card move para coluna "Pronto"
7. Toast: "Pedido #2iqpg6j8 movido para Pronto"
```

### 2. Receber novo pedido via WebSocket
```
1. Backend envia evento: order.created
2. Hook useRealtimeOrders recebe o evento
3. onOrderCreated normaliza os dados
4. Novo card aparece no topo de "Em Preparo"
5. Toast: "Novo pedido #abc123 criado!"
```

### 3. Atualizar lista manualmente
```
1. Usuário clica no botão "Atualizar"
2. Ícone gira (loading)
3. loadOrders() é chamado
4. GET /orders busca dados atualizados
5. Lista é reconstruída
6. Loading para
```

---

## 🐛 Troubleshooting

### Drag não funciona?
- ✅ Verifique se os IDs têm prefixos corretos (`order-`, `column-`)
- ✅ Confirme que `identify` existe em cada pedido
- ✅ Tente clicar e segurar por 1 segundo antes de arrastar

### Badge sempre "Offline"?
- ✅ Verifique configuração do WebSocket no `.env.local`
- ✅ Confirme que backend está rodando
- ✅ Veja console para erros de conexão

### Dados não aparecem?
- ✅ Verifique estrutura do retorno da API
- ✅ Confirme que `data` ou `data.data` contém array
- ✅ Veja console.log da função `normalizeOrder()`

### Erro TypeScript?
- ✅ Execute `npx tsc --noEmit` para verificar
- ✅ Confirme que todas interfaces estão corretas
- ✅ Rode `npm run build` para validar

---

## 📦 Dependências Necessárias

```json
{
  "@dnd-kit/core": "^6.3.1",
  "@dnd-kit/utilities": "^3.2.2",
  "lucide-react": "latest",
  "sonner": "latest"
}
```

---

## ✨ Melhorias Futuras (Sugestões)

1. **Filtros**: Adicionar filtro por cliente, mesa, data
2. **Busca**: Campo de busca por número do pedido
3. **Sons**: Notificação sonora ao receber novo pedido
4. **Impressão**: Botão para imprimir pedido direto do card
5. **Timer**: Mostrar tempo decorrido desde criação
6. **Prioridade**: Tags de prioridade (urgente, normal)
7. **Multi-drag**: Arrastar múltiplos cards de uma vez
8. **Histórico**: Ver histórico de mudanças de status
9. **Export**: Exportar pedidos para CSV/PDF
10. **Notificações**: Push notifications no navegador

---

## 📝 Checklist de Validação

Antes de usar em produção, verifique:

- [ ] Drag and drop funciona em todas as colunas
- [ ] Badge mostra status correto da conexão
- [ ] Dados da API são mapeados corretamente
- [ ] Cards mostram todas informações (cliente, mesa, produtos, total)
- [ ] Toast aparece ao mover pedidos
- [ ] WebSocket atualiza em tempo real
- [ ] Botão "Atualizar" recarrega dados
- [ ] Não há erros no console
- [ ] Build TypeScript passa sem erros
- [ ] Testes unitários passam (se houver)

---

## 🎓 Arquitetura do Componente

```
OrdersBoardPage.tsx
│
├── Estado Global
│   ├── orders: Order[]
│   ├── loading: boolean
│   ├── updatingIdentify: string | null
│   └── activeOrder: Order | null
│
├── Hooks
│   ├── useAuth() - Dados do usuário
│   ├── useRealtimeOrders() - WebSocket
│   ├── useSensors() - Drag and drop
│   ├── useCallback() - Otimização
│   └── useMemo() - Agrupamento
│
├── Funções
│   ├── normalizeOrder() - Mapeia API → Order
│   ├── loadOrders() - GET /orders
│   ├── updateOrderStatus() - PUT /orders/{id}
│   ├── handleDragStart() - Inicia drag
│   └── handleDragEnd() - Finaliza drag
│
└── Componentes Filhos
    ├── BoardColumn
    │   ├── DroppableColumnArea
    │   │   └── OrderCard[]
    │   └── Badge (contador)
    └── DragOverlay
        └── OrderCard (fantasma)
```

---

## 💡 Dicas de Performance

1. **useCallback**: Todos os handlers WebSocket usam useCallback
2. **useMemo**: groupedOrders é recalculado apenas quando orders muda
3. **Keys únicas**: order.identify garante re-render eficiente
4. **Normalização**: Dados normalizados uma vez ao carregar/receber
5. **Otimistic UI**: Atualização local antes do retorno da API

---

Componente refatorado e pronto para uso! 🚀
