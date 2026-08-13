# Guia Rápido: Como Iniciar e Testar WebSocket com Laravel Reverb

## Pré-requisitos

- Backend Laravel rodando
- Frontend Next.js rodando
- Banco de dados configurado

## Passo 1: Iniciar o Servidor Reverb

Abra um terminal dedicado para o Reverb:

```bash
cd backend
php artisan reverb:start
```

Você deverá ver uma saída similar a:

```
  INFO  Starting Reverb server on 0.0.0.0:8080.

  INFO  Broadcasting health checks...

  INFO  Reverb server started.
```

**Importante:** Deixe este terminal aberto. O servidor WebSocket precisa estar rodando continuamente.

## Passo 2: Iniciar o Backend Laravel

Em outro terminal:

```bash
cd backend
php artisan serve
```

O backend estará disponível em `http://localhost:8000` ou `http://localhost` dependendo da sua configuração.

## Passo 3: Iniciar o Frontend Next.js

Em outro terminal:

```bash
cd frontend
npm run dev
```

O frontend estará disponível em `http://localhost:3000`.

## Passo 4: Testar a Funcionalidade em Tempo Real

### Teste 1: Conexão WebSocket

1. Acesse `http://localhost:3000/login`
2. Faça login com suas credenciais
3. Navegue para `http://localhost:3000/orders/board`
4. Observe o badge no canto superior direito que deve mostrar:
   - 🟢 **"Tempo real ativo"** se conectado
   - 🔴 **"Offline"** se desconectado

### Teste 2: Atualização em Tempo Real

1. **Abra duas abas/janelas do navegador** lado a lado
2. Em ambas, acesse `http://localhost:3000/orders/board`
3. Em uma aba, crie um novo pedido ou arraste um pedido para outra coluna
4. **Observe a atualização automática na segunda aba**
5. Você deve ver:
   - O pedido aparecer instantaneamente na outra aba
   - Uma notificação toast informando a mudança
   - O contador de pedidos atualizar em tempo real

### Teste 3: Múltiplos Usuários (Opcional)

1. Abra o navegador em modo anônimo ou use outro navegador
2. Faça login com um usuário diferente do mesmo tenant
3. Repita o teste anterior
4. Ambos os usuários devem ver as atualizações em tempo real

## Verificar Logs

### Logs do Reverb

No terminal do Reverb, você verá mensagens quando:
- Clientes se conectam
- Eventos são transmitidos
- Clientes se desconectam

Exemplo:
```
  INFO  Connection opened on app 586817.

  INFO  Subscribed to tenant.1.orders

  INFO  Broadcasting event [order.created]
```

### Logs do Frontend

Abra o DevTools do navegador (F12) e vá para a aba Console. Você verá:

```
Echo: Initialized successfully
useRealtimeOrders: Subscribing to channel: tenant.1.orders
useRealtimeOrders: Successfully subscribed to tenant.1.orders
Real-time: Order status updated {order: {...}, oldStatus: "Em Preparo", newStatus: "Pronto"}
```

### Logs do Backend

No terminal do Laravel, você verá requisições para:
```
POST /broadcasting/auth - Autenticação de canal
```

## Troubleshooting

### Problema: Badge mostra "Offline"

**Soluções:**

1. **Verificar se o Reverb está rodando:**
   ```bash
   ps aux | grep reverb
   ```

2. **Verificar se a porta 8080 está aberta:**
   ```bash
   lsof -i :8080
   ```

3. **Verificar configurações do .env (Backend):**
   ```env
   BROADCAST_DRIVER=reverb
   REVERB_APP_KEY=kgntgjptuwjk1elaoq4a
   REVERB_HOST=localhost
   REVERB_PORT=8080
   ```

4. **Verificar configurações do .env.local (Frontend):**
   ```env
   NEXT_PUBLIC_REVERB_APP_KEY=kgntgjptuwjk1elaoq4a
   NEXT_PUBLIC_REVERB_HOST=localhost
   NEXT_PUBLIC_REVERB_PORT=8080
   NEXT_PUBLIC_REVERB_SCHEME=http
   ```

5. **Limpar cache do navegador e recarregar a página**

### Problema: Eventos não são recebidos

**Soluções:**

1. **Verificar console do navegador** para erros de JavaScript

2. **Verificar se o token está válido:**
   - Abra DevTools → Application → Local Storage
   - Procure por `token` ou `auth-token`
   - Verifique se existe e começa com `eyJ`

3. **Verificar autenticação do canal:**
   - No console do backend, procure por `POST /broadcasting/auth`
   - Verifique se retorna 200 OK

4. **Verificar tenant_id:**
   - O usuário logado deve ter um `tenant_id` válido
   - Verifique no console do navegador: `console.log(user?.tenant_id)`

### Problema: Erro "Connection refused" ao conectar

**Solução:**

Reinicie o servidor Reverb:
```bash
# Parar o servidor (Ctrl+C)
php artisan reverb:restart
```

## Comandos Úteis

### Ver processos rodando do Reverb
```bash
ps aux | grep reverb
```

### Matar processo do Reverb (se necessário)
```bash
pkill -f "php artisan reverb"
```

### Reiniciar Reverb
```bash
php artisan reverb:restart
```

### Verificar configuração do Reverb
```bash
php artisan config:show broadcasting
```

## Próximos Passos

Após confirmar que o sistema está funcionando:

1. Explore outros recursos implementados:
   - Canal de presença para ver usuários online
   - Notificações toast personalizadas
   - Sincronização automática

2. Personalize os eventos:
   - Adicione mais informações aos eventos
   - Crie eventos personalizados para outras entidades

3. Monitore a performance:
   - Use o Laravel Telescope para monitorar eventos
   - Verifique a latência das atualizações

4. Deploy em produção:
   - Configure SSL/TLS para WSS
   - Use um processo supervisor (PM2, Supervisor)
   - Configure proxy reverso (Nginx, Apache)

## Notas Importantes

- **Não feche o terminal do Reverb** enquanto estiver testando
- **Use sempre a mesma chave** REVERB_APP_KEY no backend e frontend
- **Para produção**, considere usar WSS (WebSocket Secure) com HTTPS
- **Monitore o uso de recursos** do Reverb em produção
- **Configure supervisores** para garantir que o Reverb reinicie em caso de falha

## Checklist de Verificação

- [ ] Servidor Reverb rodando na porta 8080
- [ ] Backend Laravel rodando
- [ ] Frontend Next.js rodando
- [ ] Usuário autenticado com tenant_id válido
- [ ] Badge "Tempo real ativo" aparecendo
- [ ] Console do navegador sem erros
- [ ] Teste de atualização em duas abas funcionando
- [ ] Notificações toast aparecem ao atualizar

## Sucesso!

Se todos os itens do checklist estão marcados, parabéns! Seu sistema de colaboração em tempo real está funcionando perfeitamente. 🎉

Para mais detalhes técnicos, consulte `IMPLEMENTACAO_WEBSOCKET_KANBAN.md`.
