# 🔌 Explicação: Badge isConnected - Online/Offline

## 📋 Como Funciona o Badge de Conexão

O badge que mostra **"Online"** ou **"Offline"** no Quadro de Pedidos indica o status da conexão **WebSocket** (Reverb).

### ✅ Status "Online" (Verde)
```
Condições para mostrar "Online":
1. ✅ Servidor Reverb está rodando (localhost:8080)
2. ✅ Frontend conseguiu conectar ao WebSocket
3. ✅ Canal privado foi subscrito com sucesso
4. ✅ Token JWT é válido para autenticação
```

### ⚠️ Status "Offline" (Cinza)
```
Condições para mostrar "Offline":
1. ❌ Servidor Reverb NÃO está rodando
2. ❌ Porta 8080 não está acessível
3. ❌ Erro na autenticação do canal
4. ❌ Token JWT inválido ou expirado
```

---

## 🔍 Fluxo de Conexão

### Passo 1: Inicialização do Echo
```typescript
// frontend/src/lib/echo.ts
export const initializeEcho = () => {
  const token = localStorage.getItem('token')
  
  if (!token) {
    console.info('Echo: Waiting for authentication...')
    return null  // ❌ Sem token = sem conexão
  }

  const echo = new Echo({
    broadcaster: 'reverb',
    wsHost: 'localhost',
    wsPort: 8080,
    authEndpoint: 'http://localhost/broadcasting/auth',
    auth: {
      headers: {
        Authorization: `Bearer ${token}`,
      },
    },
  })
  
  return echo  // ✅ Echo criado
}
```

### Passo 2: Subscrição ao Canal
```typescript
// frontend/src/hooks/use-realtime.ts
const echo = initializeEcho()

if (!echo) {
  setIsConnected(false)  // ❌ Echo não disponível
  return
}

const channel = echo.private(`tenant.${tenantId}.orders`)

// Quando subscrição é bem-sucedida
channel.subscribed(() => {
  console.log('Successfully subscribed')
  setIsConnected(true)  // ✅ ONLINE!
})

// Quando há erro
channel.error((error) => {
  console.error('Channel error', error)
  setIsConnected(false)  // ❌ OFFLINE!
})
```

### Passo 3: Exibição no Badge
```typescript
// frontend/src/app/(dashboard)/orders/board/page.tsx
<Badge variant={isConnected ? "default" : "secondary"}>
  {isConnected ? (
    <Wifi className="h-3.5 w-3.5" />  // ✅ Ícone Online
  ) : (
    <WifiOff className="h-3.5 w-3.5" />  // ❌ Ícone Offline
  )}
  <span>{isConnected ? "Online" : "Offline"}</span>
</Badge>
```

---

## 🚀 Como Fazer o Badge Mostrar "Online"

### Opção 1: Iniciar Servidor Reverb
```bash
# Terminal 1 - Backend
cd backend
php artisan reverb:start

# Saída esperada:
# Starting server on 0.0.0.0:8080...
# Server running!
```

### Opção 2: Verificar se já está rodando
```bash
# Verificar se porta 8080 está em uso
lsof -i :8080

# Testar conexão
curl http://localhost:8080
# Resposta: "Reverb is running"
```

### Opção 3: Docker (se configurado)
```bash
docker-compose up -d reverb
```

---

## 🐛 Troubleshooting

### Badge sempre "Offline"?

#### Causa 1: Reverb não está rodando
```bash
# Verificar
lsof -i :8080
# Se vazio, Reverb não está rodando

# Solução
php artisan reverb:start
```

#### Causa 2: Porta 8080 bloqueada
```bash
# Verificar firewall
sudo ufw status

# Temporariamente usar outra porta
php artisan reverb:start --port=8081

# Atualizar .env.local do frontend
NEXT_PUBLIC_REVERB_PORT=8081
```

#### Causa 3: Token JWT expirado
```bash
# Fazer logout e login novamente
# O token será renovado automaticamente
```

#### Causa 4: CORS não configurado
```php
// backend/config/cors.php
'paths' => [
    'api/*', 
    'broadcasting/auth',  // ✅ Necessário!
],
```

#### Causa 5: Variáveis de ambiente incorretas
```bash
# Frontend .env.local
NEXT_PUBLIC_REVERB_HOST=localhost  # ✅ Correto
NEXT_PUBLIC_REVERB_PORT=8080       # ✅ Correto
NEXT_PUBLIC_REVERB_APP_KEY=kgntgjptuwjk1elaoq4a  # ✅ Mesmo do backend

# Backend .env
REVERB_APP_KEY=kgntgjptuwjk1elaoq4a  # ✅ Deve ser igual
REVERB_PORT=8080                      # ✅ Mesma porta
```

---

## 📝 Logs Úteis para Debug

### Console do Navegador (Frontend)
```javascript
// ✅ Sucesso
Echo: Initialized successfully { host: 'localhost', port: 8080 }
useRealtimeOrders: Subscribing to channel: tenant.1.orders
useRealtimeOrders: Successfully subscribed to tenant.1.orders

// ❌ Erro
Echo: Could not initialize WebSocket (optional feature)
useRealtimeOrders: WebSocket not available (optional feature)
useRealtimeOrders: Channel error on tenant.1.orders
```

### Terminal Backend (Reverb)
```bash
# ✅ Sucesso - Cliente conectado
[2025-10-05 21:00:00] Connection established: client-123
[2025-10-05 21:00:01] Subscribed to: private-tenant.1.orders

# ❌ Erro - Autenticação falhou
[2025-10-05 21:00:00] Authentication failed: Invalid token
```

---

## ⚙️ Configuração Completa

### Backend (.env)
```env
BROADCAST_DRIVER=reverb

REVERB_APP_ID=586817
REVERB_APP_KEY=kgntgjptuwjk1elaoq4a
REVERB_APP_SECRET=gckv5wihfyan3sinvj8v
REVERB_HOST="localhost"
REVERB_PORT=8080
REVERB_SCHEME=http
```

### Frontend (.env.local)
```env
NEXT_PUBLIC_API_URL=http://localhost
NEXT_PUBLIC_REVERB_HOST=localhost
NEXT_PUBLIC_REVERB_PORT=8080
NEXT_PUBLIC_REVERB_APP_KEY=kgntgjptuwjk1elaoq4a
NEXT_PUBLIC_REVERB_SCHEME=http
```

### Iniciar Sistema Completo
```bash
# Terminal 1 - Reverb (WebSocket)
cd backend
php artisan reverb:start

# Terminal 2 - Backend API
cd backend
php artisan serve

# Terminal 3 - Frontend
cd frontend
npm run dev
```

---

## ✅ Teste de Validação

### Teste 1: Badge Online
```bash
1. Inicie Reverb: php artisan reverb:start
2. Abra: http://localhost:3000/orders/board
3. ✅ Badge deve mostrar "Online" (verde)
4. Console deve logar: "Successfully subscribed"
```

### Teste 2: Badge Offline
```bash
1. Pare Reverb (Ctrl+C)
2. Recarregue: http://localhost:3000/orders/board
3. ✅ Badge deve mostrar "Offline" (cinza)
4. Console deve logar: "WebSocket not available"
```

### Teste 3: Real-time Funciona
```bash
1. Inicie Reverb
2. Abra 2 abas do navegador
3. Badge "Online" em ambas
4. Mova um card na aba 1
5. ✅ Aba 2 atualiza automaticamente
```

---

## 🎯 Comportamento Esperado

### ✅ COM Reverb Rodando
```
1. Badge mostra "Online" (verde + ícone Wifi)
2. Drag and drop funciona
3. WebSocket atualiza em tempo real
4. Outras tabs recebem atualizações
5. Logs mostram "Successfully subscribed"
```

### ✅ SEM Reverb Rodando
```
1. Badge mostra "Offline" (cinza + ícone WifiOff)
2. Drag and drop funciona normalmente
3. WebSocket não funciona (esperado)
4. Outras tabs NÃO recebem atualizações
5. Logs mostram "WebSocket not available"
```

**IMPORTANTE:** O sistema funciona perfeitamente **COM ou SEM Reverb**! O WebSocket é um recurso **opcional** para real-time, mas não é obrigatório para o funcionamento básico.

---

## 📊 Resumo

| Componente | Arquivo | Responsabilidade |
|------------|---------|------------------|
| **Echo Init** | `lib/echo.ts` | Cria instância Echo com config Reverb |
| **Realtime Hook** | `hooks/use-realtime.ts` | Gerencia conexão e eventos |
| **Badge UI** | `orders/board/page.tsx` | Exibe status visual |
| **Reverb Server** | Backend (porta 8080) | Servidor WebSocket |

---

## 🔗 Referências

- [Laravel Reverb Docs](https://laravel.com/docs/11.x/reverb)
- [Laravel Echo Docs](https://laravel.com/docs/11.x/broadcasting#client-side-installation)
- [Pusher Protocol](https://pusher.com/docs/channels/library_auth_reference/pusher-websockets-protocol/)

---

**Status Atual:**
- ✅ Badge implementado corretamente
- ✅ Mostra "Online" quando Reverb está rodando
- ✅ Mostra "Offline" quando Reverb está parado
- ✅ Sistema funciona em ambos os casos
- ✅ WebSocket é opcional, não obrigatório

**Para usar real-time:** `php artisan reverb:start`
**Para usar sem real-time:** Apenas ignore o badge "Offline"
