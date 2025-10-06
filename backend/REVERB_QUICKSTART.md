# 🚀 Laravel Reverb - Quick Start

## ⚡ Início Rápido

Este guia mostra como começar a usar o Laravel Reverb em 5 minutos.

---

## 1️⃣ Iniciar o Reverb

```bash
cd backend

# Opção 1: Usando o script gerenciador
./reverb.sh start

# Opção 2: Usando docker-compose diretamente
docker-compose up -d reverb

# Verificar se está rodando
./reverb.sh status
```

**Resultado esperado**:
```
✓ Reverb iniciado com sucesso!
  Acesso: ws://localhost:8080
```

---

## 2️⃣ Criar seu Primeiro Event

**Crie**: `backend/app/Events/TestEvent.php`

```php
<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TestEvent implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public string $message,
        public array $data = []
    ) {}

    public function broadcastOn(): Channel
    {
        return new Channel('test');
    }

    public function broadcastAs(): string
    {
        return 'message';
    }
}
```

---

## 3️⃣ Testar o Event no Backend

```bash
# Abrir tinker
docker-compose exec laravel.test php artisan tinker
```

```php
// Disparar o evento
event(new App\Events\TestEvent(
    message: 'Hello from Reverb!',
    data: ['timestamp' => now()]
));
```

---

## 4️⃣ Ouvir o Event no Frontend

**Crie**: `frontend/src/components/test-reverb.tsx`

```typescript
'use client'

import { useEffect, useState } from 'react'
import { initializeEcho } from '@/lib/echo'

export function TestReverb() {
  const [messages, setMessages] = useState<string[]>([])
  const [isConnected, setIsConnected] = useState(false)

  useEffect(() => {
    const echo = initializeEcho()
    
    if (!echo) {
      console.error('Echo não inicializado')
      return
    }

    setIsConnected(true)

    const channel = echo.channel('test')
    
    channel.listen('.message', (event: any) => {
      console.log('Mensagem recebida:', event)
      setMessages(prev => [...prev, event.message])
    })

    return () => {
      echo.leave('test')
    }
  }, [])

  return (
    <div className="p-4 border rounded-lg">
      <h2 className="text-xl font-bold mb-4">
        Laravel Reverb Test
      </h2>
      
      <div className="mb-4">
        Status: {' '}
        <span className={isConnected ? 'text-green-600' : 'text-red-600'}>
          {isConnected ? '🟢 Conectado' : '🔴 Desconectado'}
        </span>
      </div>

      <div className="space-y-2">
        <p className="font-semibold">Mensagens recebidas:</p>
        {messages.length === 0 ? (
          <p className="text-gray-500 italic">
            Nenhuma mensagem ainda. Execute o event no backend.
          </p>
        ) : (
          messages.map((msg, idx) => (
            <div key={idx} className="p-2 bg-gray-100 rounded">
              {msg}
            </div>
          ))
        )}
      </div>
    </div>
  )
}
```

**Adicione na sua página**:

```typescript
// app/test/page.tsx
import { TestReverb } from '@/components/test-reverb'

export default function TestPage() {
  return (
    <div className="container mx-auto py-8">
      <TestReverb />
    </div>
  )
}
```

---

## 5️⃣ Testar Tudo Junto

1. **Abra o frontend**: `http://localhost:3000/test`
2. **Abra o console** do navegador (F12)
3. **No backend**, execute no tinker:

```php
event(new App\Events\TestEvent(
    message: 'Teste de WebSocket funcionando!',
    data: ['source' => 'tinker']
));
```

4. **Observe** a mensagem aparecer em tempo real no frontend! 🎉

---

## 🎯 Exemplo Real: Notificação de Novo Pedido

### Backend - Event

```php
<?php

namespace App\Events;

use App\Models\Order;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class NewOrderCreated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Order $order
    ) {}

    public function broadcastOn(): Channel
    {
        return new Channel('kitchen');
    }

    public function broadcastAs(): string
    {
        return 'new.order';
    }

    public function broadcastWith(): array
    {
        return [
            'id' => $this->order->id,
            'identify' => $this->order->identify,
            'table_id' => $this->order->table_id,
            'total' => $this->order->total,
            'status' => $this->order->status,
            'items_count' => $this->order->products->count(),
        ];
    }
}
```

### Backend - Disparar ao Criar Pedido

```php
// No OrderController ou Service
public function store(CreateOrderRequest $request)
{
    $order = Order::create($request->validated());
    
    // Disparar evento em tempo real
    event(new NewOrderCreated($order));
    
    return response()->json([
        'success' => true,
        'data' => $order,
    ]);
}
```

### Frontend - Component da Cozinha

```typescript
'use client'

import { useEffect, useState } from 'react'
import { initializeEcho } from '@/lib/echo'
import { toast } from 'sonner'

export function KitchenNotifications() {
  const [newOrders, setNewOrders] = useState(0)

  useEffect(() => {
    const echo = initializeEcho()
    if (!echo) return

    const channel = echo.channel('kitchen')
    
    channel.listen('.new.order', (event: any) => {
      // Mostrar notificação toast
      toast.success(`Novo Pedido #${event.identify}`, {
        description: `Mesa ${event.table_id} - ${event.items_count} itens`,
        duration: 5000,
      })
      
      // Incrementar contador
      setNewOrders(prev => prev + 1)
      
      // Tocar som (opcional)
      const audio = new Audio('/notification.mp3')
      audio.play()
    })

    return () => {
      echo.leave('kitchen')
    }
  }, [])

  return (
    <div className="fixed top-4 right-4 bg-red-500 text-white rounded-full w-12 h-12 flex items-center justify-center">
      {newOrders}
    </div>
  )
}
```

---

## 🛠️ Comandos Úteis

```bash
# Iniciar Reverb
./reverb.sh start

# Ver logs em tempo real
./reverb.sh logs

# Ver status
./reverb.sh status

# Testar conexão
./reverb.sh test

# Parar Reverb
./reverb.sh stop

# Reiniciar Reverb
./reverb.sh restart
```

---

## 🐛 Problemas Comuns

### 1. Eventos não chegam no frontend

**Checklist**:
- [ ] Reverb está rodando? (`./reverb.sh status`)
- [ ] Event implementa `ShouldBroadcast`?
- [ ] `BROADCAST_DRIVER=reverb` no .env?
- [ ] Echo foi inicializado? (`initializeEcho()`)
- [ ] Nome do canal está correto?
- [ ] Nome do event tem `.` na frente? (`.message`, `.new.order`)

### 2. "Echo not initialized"

```typescript
// Certifique-se de que o Echo seja inicializado após o login
import { initializeEcho } from '@/lib/echo'

// Em um useEffect após autenticação
useEffect(() => {
  if (isAuthenticated) {
    initializeEcho()
  }
}, [isAuthenticated])
```

### 3. Container não inicia

```bash
# Ver erro específico
docker-compose logs reverb

# Reconstruir
docker-compose up -d --build reverb
```

---

## 📚 Próximos Passos

1. ✅ Criar events para seus módulos específicos
2. ✅ Implementar canais privados (notificações por usuário)
3. ✅ Adicionar sons nas notificações
4. ✅ Criar dashboard com estatísticas em tempo real
5. ✅ Implementar quadro Kanban colaborativo

---

## 🎓 Recursos de Aprendizado

- [REVERB_IMPLEMENTATION_SUMMARY.md](./REVERB_IMPLEMENTATION_SUMMARY.md) - Resumo completo
- [REVERB_SETUP_COMPLETE.md](../REVERB_SETUP_COMPLETE.md) - Guia com exemplos
- [Laravel Reverb Docs](https://laravel.com/docs/11.x/reverb)

---

**Pronto!** 🎉 Agora você tem WebSocket em tempo real funcionando na sua aplicação!
