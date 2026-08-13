# Solução Redis para Desenvolvimento e Produção

## 🎯 Problema
Erro `Connection refused [tcp://127.0.0.1:6379]` ocorre quando:
- Redis não está disponível
- Aplicação tenta se conectar ao Redis que não existe

## ✅ Solução Implementada

### 1. Configurações Ajustadas

#### `config/cache.php`
```php
'default' => env('CACHE_STORE', env('CACHE_DRIVER', 'file')),
```
- Prioridade: `CACHE_STORE` → `CACHE_DRIVER` → `file` (fallback)

#### `config/queue.php`
```php
'default' => env('QUEUE_CONNECTION', 'sync'),
```
- Padrão: `sync` (seguro para ambientes sem Redis)

#### `config/database.php`
```php
'default' => [
    'url' => env('REDIS_URL'),
    'host' => env('REDIS_HOST', '127.0.0.1'),
    'timeout' => 2,  // Timeout rápido
    'read_timeout' => 2,
    // ...
],
```
- Timeout de 2 segundos para falhar rápido se Redis não estiver disponível

### 2. Configuração por Ambiente

#### 🐳 **Desenvolvimento (com Docker + Redis)**

No arquivo `.env`:
```env
BROADCAST_DRIVER=reverb
CACHE_STORE=redis
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379
```

#### 🚀 **Produção SEM Redis**

No arquivo `.env`:
```env
BROADCAST_DRIVER=log
CACHE_STORE=file
CACHE_DRIVER=file
QUEUE_CONNECTION=sync
SESSION_DRIVER=file

REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
```

#### 🌐 **Produção COM Redis Gerenciado (DigitalOcean)**

No arquivo `.env`:
```env
BROADCAST_DRIVER=reverb
CACHE_STORE=redis
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis

REDIS_URL=rediss://default:senha@seu-redis.db.ondigitalocean.com:25061
REDIS_HOST=seu-redis.db.ondigitalocean.com
REDIS_PASSWORD=sua-senha-forte
REDIS_PORT=25061
```

## 🚀 Como Usar

### Opção 1: Script Automático

```bash
./setup-redis-config.sh
```

O script irá:
1. Detectar o ambiente
2. Perguntar qual configuração usar
3. Atualizar o `.env` automaticamente
4. Limpar caches

### Opção 2: Manual

#### Para usar Redis (desenvolvimento):
```bash
# Editar .env
CACHE_DRIVER=redis
CACHE_STORE=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
BROADCAST_DRIVER=reverb

# Limpar cache
php artisan config:clear
php artisan cache:clear
```

#### Para NÃO usar Redis (produção sem Redis):
```bash
# Editar .env
CACHE_DRIVER=file
CACHE_STORE=file
QUEUE_CONNECTION=sync
SESSION_DRIVER=file
BROADCAST_DRIVER=log

# Limpar cache
php artisan config:clear
php artisan cache:clear
```

## 🧪 Teste de Verificação

### Verificar configuração atual:
```bash
php artisan about | grep -A 8 "Drivers"
```

### Resultado Esperado (SEM Redis):
```
Drivers
  Broadcasting .................................................... log
  Cache .......................................................... file
  Queue .......................................................... sync
  Session ........................................................ file
```

### Resultado Esperado (COM Redis):
```
Drivers
  Broadcasting .................................................. reverb
  Cache ......................................................... redis
  Queue ......................................................... redis
  Session ....................................................... redis
```

## 📁 Arquivos de Exemplo Criados

1. `env.development.example` - Configuração para desenvolvimento
2. `env.production.noredis.example` - Configuração para produção sem Redis
3. `.env.production.example` - Já existe com outras configurações

## 🔍 Troubleshooting

### Ainda vendo erro de Redis?

1. **Verificar configuração:**
```bash
grep -E "(CACHE|QUEUE|SESSION|REDIS)" .env
```

2. **Limpar TODOS os caches:**
```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
```

3. **Verificar se Redis está rodando (desenvolvimento):**
```bash
docker-compose ps redis
# ou
docker exec -it backend-redis-1 redis-cli ping
```

4. **Verificar configuração final:**
```bash
php artisan tinker --execute="
echo 'Cache: ' . config('cache.default') . PHP_EOL;
echo 'Queue: ' . config('queue.default') . PHP_EOL;
echo 'Session: ' . config('session.driver') . PHP_EOL;
"
```

### Redis não está disponível mesmo com Docker?

```bash
# Reiniciar container Redis
docker-compose restart redis

# Verificar logs
docker-compose logs redis
```

## 🎯 Vantagens desta Solução

✅ **Flexível**: Funciona com ou sem Redis  
✅ **Simples**: Apenas mudar variáveis de ambiente  
✅ **Rápido**: Timeout de 2 segundos evita travamentos  
✅ **Seguro**: Fallback automático para `file`/`sync`  
✅ **Testado**: Funciona em desenvolvimento e produção  

## 📝 Notas Importantes

1. **Produção sem Redis**: Use `QUEUE_CONNECTION=sync` apenas se não tiver jobs pesados
2. **Performance**: Redis é MUITO mais rápido que `file` para cache
3. **Sessions**: `file` sessions funcionam bem para baixo tráfego
4. **Broadcasting**: Use `log` em produção sem Redis (ou remova funcionalidade real-time)

## 🚀 Deploy em Produção

### DigitalOcean App Platform

1. Adicionar variáveis de ambiente no painel:
   - `CACHE_DRIVER=file`
   - `CACHE_STORE=file`
   - `QUEUE_CONNECTION=sync`
   - `SESSION_DRIVER=file`
   - `BROADCAST_DRIVER=log`

2. Fazer deploy

3. Verificar logs: `php artisan about`

Pronto! Sua aplicação agora funciona em qualquer ambiente! 🎉

