# Implementação de WebSocket com Laravel Reverb para Quadro Kanban

## Resumo da Implementação

Este documento descreve a implementação completa de um sistema de comunicação em tempo real utilizando Laravel Reverb para o quadro Kanban de pedidos, permitindo colaboração em tempo real entre múltiplos usuários.

## Backend - Laravel

### 1. Instalação e Configuração do Laravel Reverb

**Pacote instalado:**
```bash
composer require laravel/reverb
php artisan reverb:install
```

**Configuração no .env:**
```env
BROADCAST_DRIVER=reverb
REVERB_APP_ID=586817
REVERB_APP_KEY=kgntgjptuwjk1elaoq4a
REVERB_APP_SECRET=gckv5wihfyan3sinvj8v
REVERB_HOST="localhost"
REVERB_PORT=8080
REVERB_SCHEME=http
```

### 2. Eventos de Broadcasting Criados

#### **OrderCreated** (`app/Events/OrderCreated.php`)
- Disparado quando um novo pedido é criado
- Transmite para o canal privado do tenant
- Inclui todos os dados do pedido com relacionamentos (cliente, mesa, produtos)

#### **OrderStatusUpdated** (`app/Events/OrderStatusUpdated.php`)
- Disparado quando o status de um pedido é alterado
- Transmite status anterior e novo status
- Ideal para atualizar visualmente o movimento no Kanban

#### **OrderUpdated** (`app/Events/OrderUpdated.php`)
- Disparado quando qualquer dado do pedido é atualizado (exceto status)
- Garante sincronização de todas as informações do pedido

### 3. Canais de Broadcasting

**Configuração em `routes/channels.php`:**

```php
// Canal privado para pedidos de um tenant específico
Broadcast::channel('tenant.{tenantId}.orders', function ($user, $tenantId) {
    return $user->tenant_id === (int) $tenantId;
});

// Canal de presença para ver quem está online
Broadcast::channel('tenant.{tenantId}.presence', function ($user, $tenantId) {
    if ($user->tenant_id === (int) $tenantId) {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
        ];
    }
    return false;
});
```

### 4. Integração com OrderService

**Modificações em `app/Services/OrderService.php`:**

- Método `createNewOrder()`: Dispara evento `OrderCreated` após criação
- Método `updateOrder()`: Dispara evento `OrderStatusUpdated` ou `OrderUpdated` conforme a alteração

### 5. Rota de Autenticação de Broadcasting

**Adicionado em `routes/api.php`:**
```php
Route::middleware(['auth:api'])->group(function () {
    Route::post('/broadcasting/auth', function (Request $request) {
        return \Illuminate\Support\Facades\Broadcast::auth($request);
    });
});
```

## Frontend - React/Next.js

### 1. Dependências Instaladas

```bash
npm install laravel-echo pusher-js
```

### 2. Configuração do Echo

**Arquivo criado: `src/lib/echo.ts`**

Configuração centralizada do Laravel Echo com:
- Suporte ao Reverb como broadcaster
- Autenticação via JWT token
- Configuração de host, porta e esquema via variáveis de ambiente
- Funções auxiliares para inicialização e desconexão

### 3. Hook Personalizado de Real-time

**Arquivo criado: `src/hooks/use-realtime.ts`**

Dois hooks principais:

#### **useRealtimeOrders**
```typescript
interface UseRealtimeOrdersOptions {
  tenantId: number
  onOrderCreated?: (order: any) => void
  onOrderUpdated?: (order: any) => void
  onOrderStatusUpdated?: (data: { order: any; oldStatus: string; newStatus: string }) => void
  enabled?: boolean
}
```

Funcionalidades:
- Conexão automática ao canal de pedidos do tenant
- Callbacks para cada tipo de evento
- Gerenciamento automático de conexão/desconexão
- Indicador de status de conexão

#### **usePresence**
```typescript
interface UsePresenceOptions {
  tenantId: number
  onJoin?: (user: any) => void
  onLeave?: (user: any) => void
  onHere?: (users: any[]) => void
  enabled?: boolean
}
```

Funcionalidades:
- Lista de usuários atualmente online
- Callbacks para entrada/saída de usuários
- Canal de presença do Laravel

### 4. Quadro Kanban com Real-time

**Arquivo atualizado: `src/app/(dashboard)/orders/board/page.tsx`**

Implementações:
- Integração do hook `useRealtimeOrders`
- Atualização automática da UI quando pedidos são criados/atualizados
- Indicador visual de conexão WebSocket
- Prevenção de duplicatas ao receber eventos
- Notificações toast para mudanças em tempo real
- Sincronização automática entre múltiplos usuários

### 5. Variáveis de Ambiente

**Arquivo criado: `frontend/.env.local`**
```env
NEXT_PUBLIC_API_URL=http://localhost
NEXT_PUBLIC_REVERB_APP_KEY=kgntgjptuwjk1elaoq4a
NEXT_PUBLIC_REVERB_HOST=localhost
NEXT_PUBLIC_REVERB_PORT=8080
NEXT_PUBLIC_REVERB_SCHEME=http
```

## Como Usar

### 1. Iniciar o Servidor Reverb

```bash
cd backend
php artisan reverb:start
```

O servidor WebSocket estará disponível em `ws://localhost:8080`

### 2. Iniciar o Backend Laravel

```bash
cd backend
php artisan serve
```

### 3. Iniciar o Frontend Next.js

```bash
cd frontend
npm run dev
```

### 4. Testar a Funcionalidade

1. Abra o quadro Kanban em múltiplas abas/navegadores
2. Crie um novo pedido em uma aba
3. Observe a atualização em tempo real em todas as abas abertas
4. Arraste um pedido para outra coluna
5. Veja a mudança refletida instantaneamente em todas as sessões

## Recursos Implementados

### ✅ Broadcasting de Eventos
- Criação de pedidos
- Atualização de status
- Atualização de dados gerais

### ✅ Canais Privados
- Autenticação baseada em tenant
- Isolamento por organização
- Segurança de acesso

### ✅ Canal de Presença
- Lista de usuários online
- Notificação de entrada/saída

### ✅ UI em Tempo Real
- Indicador de conexão WebSocket
- Notificações toast para eventos
- Sincronização automática
- Prevenção de conflitos

## Próximos Passos Sugeridos

1. **Indicador de "Quem está editando"**: Mostrar quando outro usuário está editando um pedido
2. **Sincronização otimista**: Atualizar UI antes de confirmar com servidor
3. **Reconexão automática**: Implementar retry logic para quedas de conexão
4. **Compressão de eventos**: Agrupar múltiplas atualizações em um único evento
5. **Histórico de atividades**: Log visual de mudanças em tempo real

## Benefícios

1. **Colaboração em Tempo Real**: Múltiplos usuários podem trabalhar simultaneamente
2. **Redução de Conflitos**: Usuários veem mudanças imediatamente
3. **Melhor UX**: Feedback instantâneo de ações
4. **Escalabilidade**: Reverb é otimizado para performance
5. **Manutenibilidade**: Código modular e reutilizável

## Troubleshooting

### Problema: "Echo: No auth token found"
**Solução**: Certifique-se de que o usuário está autenticado antes de acessar a página

### Problema: Conexão WebSocket falha
**Solução**: 
1. Verifique se o servidor Reverb está rodando
2. Confirme as configurações em .env.local
3. Verifique o firewall/portas abertas

### Problema: Eventos não são recebidos
**Solução**:
1. Verifique os logs do Laravel
2. Confirme que o tenant_id está correto
3. Verifique as permissões do canal em `channels.php`

## Arquivos Modificados/Criados

### Backend
- ✅ `app/Events/OrderCreated.php` (criado)
- ✅ `app/Events/OrderStatusUpdated.php` (criado)
- ✅ `app/Events/OrderUpdated.php` (criado)
- ✅ `routes/channels.php` (atualizado)
- ✅ `routes/api.php` (atualizado)
- ✅ `app/Services/OrderService.php` (atualizado)

### Frontend
- ✅ `src/lib/echo.ts` (criado)
- ✅ `src/hooks/use-realtime.ts` (criado)
- ✅ `src/app/(dashboard)/orders/board/page.tsx` (atualizado)
- ✅ `.env.local` (criado)

## Conclusão

A implementação do Laravel Reverb para o quadro Kanban fornece uma base sólida para colaboração em tempo real. O sistema é escalável, seguro e oferece uma excelente experiência de usuário com atualizações instantâneas e sincronização automática entre múltiplas sessões.
