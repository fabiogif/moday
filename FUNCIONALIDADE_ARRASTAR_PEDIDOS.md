# Funcionalidade de Arrastar Pedidos no Quadro

## Resumo das Implementações

A funcionalidade de arrastar e soltar pedidos entre status no quadro de pedidos já estava implementada e foi aprimorada com melhorias visuais e de feedback do usuário.

## Alterações Realizadas

### 1. Frontend - Quadro de Pedidos (`frontend/src/app/(dashboard)/orders/board/page.tsx`)

#### Melhorias Implementadas:

1. **Configuração de Sensores de Arrastar**
   - Adicionado `useSensors` e `useSensor(PointerSensor)` com ativação após 8px de movimento
   - Isso evita arrastar acidental ao clicar nos cards

2. **Feedback Visual Aprimorado**
   - Cards agora mudam de aparência durante o arraste:
     - Opacidade reduzida a 50%
     - Cursor muda para "grabbing"
     - Borda destacada em azul primário
     - Sombra aumentada
   - Efeito hover nos cards com transição suave

3. **Indicação Visual nas Colunas de Destino**
   - As colunas de destino agora exibem um background destacado quando um pedido está sendo arrastado sobre elas
   - Usa `isOver` do hook `useDroppable` para detectar hover

4. **Notificações Toast Melhoradas**
   - Toast de "loading" ao iniciar o arraste
   - Toast de sucesso ao completar a mudança de status com o nome da coluna
   - Toast de erro caso a operação falhe

5. **Tratamento de Erros no WebSocket**
   - Adicionado try-catch no `initializeEcho()` para capturar erros de inicialização
   - Logs mais informativos para debug

### 2. Frontend - Echo Configuration (`frontend/src/lib/echo.ts`)

#### Melhorias:
- Adicionado tratamento de exceção na inicialização do Echo
- Melhor logging de erros para facilitar debug

## Como Funciona

### Fluxo de Arrastar e Soltar:

1. **Usuário arrasta um pedido**: O card fica com aparência semi-transparente e cursor "grabbing"

2. **Passa sobre uma coluna**: A coluna de destino fica destacada com background colorido

3. **Solta o pedido**: 
   - Toast de loading aparece
   - Requisição PUT é enviada para o backend: `/api/orders/{id}` com `{ status: "novo_status" }`
   - Backend atualiza o status no banco de dados
   - Backend dispara evento WebSocket para outros usuários (se conectado)
   - Toast de sucesso confirma a operação

4. **Atualização em Tempo Real**:
   - Outros usuários conectados via WebSocket recebem a atualização instantaneamente
   - O pedido move-se para a nova coluna sem necessidade de recarregar a página

### Status Disponíveis:

1. **Em Preparo** (Amarelo)
2. **Pronto** (Azul)
3. **Entregue** (Verde)
4. **Cancelado** (Vermelho)

## Estrutura Técnica

### Bibliotecas Utilizadas:

- `@dnd-kit/core`: Contexto e lógica de drag-and-drop
- `@dnd-kit/sortable`: Ordenação e sortable items
- `@dnd-kit/utilities`: Utilitários para transformações CSS
- `laravel-echo`: Conexão WebSocket via Reverb
- `pusher-js`: Cliente Pusher para WebSocket

### Backend:

- **Endpoint**: `PUT /api/orders/{identify}`
- **Controller**: `OrderApiController@update`
- **Service**: `OrderService::updateOrder()`
- **Validação**: `UpdateOrderRequest`
- **Broadcasting**: Eventos WebSocket disparados automaticamente ao atualizar status

## Testando a Funcionalidade

### 1. Iniciar Backend (Laravel):
```bash
cd backend
php artisan serve
php artisan reverb:start  # Para WebSocket (opcional)
```

### 2. Iniciar Frontend:
```bash
cd frontend
npm run dev
```

### 3. Acessar o Quadro:
- Navegar para: `http://localhost:3000/orders/board`
- Login com credenciais válidas

### 4. Testar Drag-and-Drop:
- Clique e segure em um card de pedido
- Arraste para outra coluna
- Solte o mouse
- Verifique o toast de confirmação
- O pedido deve aparecer na nova coluna

### 5. Testar Tempo Real (opcional):
- Abra duas abas do navegador
- Em uma aba, arraste um pedido
- Na outra aba, o pedido deve mover-se automaticamente (se WebSocket estiver ativo)

## Solução do Erro "Failed to initialize Echo"

O erro "useRealtimeOrders: Failed to initialize Echo" ocorre quando:

1. **Não há token de autenticação**: Usuário não está logado
2. **Reverb não está rodando**: Servidor WebSocket não iniciado
3. **Configuração incorreta**: Variáveis de ambiente incorretas

### Comportamento Esperado:
- Se o WebSocket falhar, o arraste ainda funciona normalmente
- A funcionalidade de drag-and-drop **não depende** do WebSocket
- O WebSocket apenas adiciona atualização em tempo real para outros usuários

### Para Resolver:

Se quiser usar WebSocket:
```bash
cd backend
php artisan reverb:start
```

Se não quiser usar WebSocket:
- A funcionalidade continua funcionando normalmente
- Apenas não haverá atualização automática em outras abas/dispositivos

## Código-Chave

### Sensor de Arraste:
```typescript
const sensors = useSensors(
  useSensor(PointerSensor, {
    activationConstraint: {
      distance: 8, // Arraste inicia após 8px
    },
  })
)
```

### Handler de Drop:
```typescript
function onDragEnd(event: DragEndEvent) {
  const { active, over } = event
  if (!over) return
  
  const orderId = Number(active.id)
  const newStatus = determineNewStatus(over)
  
  updateStatus(orderId, newStatus)
}
```

### Atualização de Status:
```typescript
async function updateStatus(orderId: number, newStatus: string) {
  await apiClient.put(endpoints.orders.update(String(orderId)), { 
    status: newStatus 
  })
}
```

## Próximos Passos (Opcional)

Se desejar melhorias adicionais:

1. **Drag Overlay**: Mostrar uma prévia do card sendo arrastado
2. **Animações**: Adicionar animações de transição entre colunas
3. **Confirmação**: Modal de confirmação antes de mudar status críticos (Ex: Cancelado)
4. **Permissões**: Restringir drag-and-drop baseado em permissões do usuário
5. **Histórico**: Registrar mudanças de status com timestamp e usuário

## Observações

- A funcionalidade está **totalmente operacional** e pronta para uso
- O WebSocket é **opcional** - a funcionalidade básica funciona sem ele
- Todas as alterações foram testadas com build de produção
- Não há erros de TypeScript ou build
