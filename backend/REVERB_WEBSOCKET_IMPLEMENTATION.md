# Implementação do Laravel Reverb - WebSocket Server

## 📋 Resumo

O Laravel Reverb foi configurado com sucesso para fornecer comunicação em tempo real via WebSocket na aplicação.

## 🐳 Configuração Docker

### Container Reverb Adicionado

Foi adicionado um novo serviço ao `docker-compose.yml`:

```yaml
reverb:
    build:
        context: ./vendor/laravel/sail/runtimes/8.3
        dockerfile: Dockerfile
        args:
            WWWGROUP: '${WWWGROUP}'
    image: sail-8.3/app
    extra_hosts:
        - 'host.docker.internal:host-gateway'
    ports:
        - '${REVERB_PORT:-8080}:8080'
    environment:
        WWWUSER: '${WWWUSER}'
        LARAVEL_SAIL: 1
    volumes:
        - '.:/var/www/html'
    networks:
        - sail
    depends_on:
        - mysql
        - redis
    command: php artisan reverb:start --host=0.0.0.0 --port=8080
```

### Características do Serviço

- **Porta exposta**: 8080 (mapeada para a porta configurada em `REVERB_PORT`)
- **Host**: 0.0.0.0 (aceita conexões de qualquer origem)
- **Dependências**: MySQL e Redis
- **Volume compartilhado**: Mesmo volume do container principal Laravel

## 🔧 Configuração no .env

As variáveis de ambiente já estavam configuradas:

```env
BROADCAST_DRIVER=reverb
REVERB_APP_ID=586817
REVERB_APP_KEY=kgntgjptuwjk1elaoq4a
REVERB_APP_SECRET=gckv5wihfyan3sinvj8v
REVERB_HOST="localhost"
REVERB_PORT=8080
REVERB_SCHEME=http

# Variáveis para o Vite (Frontend)
VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

## 🚀 Como Usar

### Iniciar o Container

```bash
# Iniciar apenas o Reverb
docker-compose up -d reverb

# Ou reiniciar todos os serviços
docker-compose up -d
```

### Verificar Status

```bash
# Ver status de todos os containers
docker-compose ps

# Ver logs do Reverb
docker-compose logs reverb

# Acompanhar logs em tempo real
docker-compose logs -f reverb
```

### Parar o Container

```bash
# Parar apenas o Reverb
docker-compose stop reverb

# Parar todos os serviços
docker-compose down
```

## 📡 Endpoints WebSocket

- **URL de conexão**: `ws://localhost:8080`
- **Protocolo**: WebSocket
- **Autenticação**: Via APP_KEY configurado

## 🔌 Integração com Frontend

No frontend React, você pode conectar ao Reverb usando:

```javascript
import Echo from 'laravel-echo'
import Pusher from 'pusher-js'

window.Pusher = Pusher

window.Echo = new Echo({
    broadcaster: 'reverb',
    key: import.meta.env.VITE_REVERB_APP_KEY,
    wsHost: import.meta.env.VITE_REVERB_HOST,
    wsPort: import.meta.env.VITE_REVERB_PORT ?? 8080,
    wssPort: import.meta.env.VITE_REVERB_PORT ?? 8080,
    forceTLS: (import.meta.env.VITE_REVERB_SCHEME ?? 'https') === 'https',
    enabledTransports: ['ws', 'wss'],
})
```

## 📝 Casos de Uso

O Laravel Reverb pode ser usado para:

1. **Quadro Kanban em Tempo Real**: Atualização automática quando usuários movem tarefas
2. **Dashboard em Tempo Real**: Estatísticas atualizadas automaticamente
3. **Notificações**: Alertas instantâneos para usuários
4. **Chat**: Mensagens em tempo real
5. **Colaboração**: Múltiplos usuários editando simultaneamente

## 🏗️ Exemplo de Broadcast Event (Backend)

```php
<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class OrderStatusUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public int $orderId,
        public string $status,
        public array $data = []
    ) {}

    public function broadcastOn(): Channel
    {
        return new Channel('orders');
    }

    public function broadcastAs(): string
    {
        return 'status.updated';
    }
}
```

Para disparar o evento:

```php
event(new OrderStatusUpdated($order->id, $order->status, [
    'table_id' => $order->table_id,
    'total' => $order->total,
]));
```

## 🎧 Exemplo de Listener (Frontend)

```javascript
// Escutar eventos no canal 'orders'
window.Echo.channel('orders')
    .listen('.status.updated', (e) => {
        console.log('Order updated:', e);
        // Atualizar a UI com os novos dados
        updateOrderInUI(e.orderId, e.status);
    });

// Para canais privados (autenticados)
window.Echo.private(`user.${userId}`)
    .listen('.notification', (e) => {
        console.log('New notification:', e);
        showNotification(e);
    });
```

## 🔒 Autenticação de Canais Privados

Para canais privados, configure as rotas de broadcasting em `routes/channels.php`:

```php
<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('user.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('orders', function ($user) {
    return $user->hasPermission('orders.view');
});
```

## ✅ Status da Implementação

- [x] Pacote Laravel Reverb instalado
- [x] Configuração do .env
- [x] Serviço Docker criado e configurado
- [x] Container em execução na porta 8080
- [x] Broadcasting configurado
- [ ] Events criados no backend (a implementar conforme necessidade)
- [ ] Echo configurado no frontend (a implementar)
- [ ] Canais privados implementados (a implementar)

## 📚 Próximos Passos

1. Criar events específicos para cada módulo (Pedidos, Usuários, etc.)
2. Configurar Echo no frontend React
3. Implementar listeners nos componentes que precisam de atualização em tempo real
4. Criar canais privados para comunicação específica por tenant
5. Adicionar autenticação de canais via middleware

## 🐛 Troubleshooting

### Container não inicia

```bash
# Verificar logs
docker-compose logs reverb

# Reconstruir o container
docker-compose up -d --build reverb
```

### Problemas de conexão

1. Verificar se a porta 8080 está acessível
2. Confirmar que o REVERB_HOST está correto no .env
3. Verificar firewall e regras de rede

### Eventos não são recebidos

1. Verificar se o evento implementa `ShouldBroadcast`
2. Confirmar que o BROADCAST_DRIVER está como 'reverb'
3. Verificar se o canal está correto no frontend

## 📖 Referências

- [Documentação Laravel Reverb](https://laravel.com/docs/11.x/reverb)
- [Laravel Broadcasting](https://laravel.com/docs/11.x/broadcasting)
- [Laravel Echo](https://laravel.com/docs/11.x/broadcasting#client-side-installation)
