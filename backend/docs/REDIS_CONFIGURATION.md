# Configuração Redis - Resumo

## ✅ Problema Resolvido

O erro `Connection refused [tcp://127.0.0.1:6379]` foi causado por:
1. Configuração incorreta do `scheme` (estava usando `tls` quando deveria ser `tcp`)
2. Variável de ambiente desatualizada (`CACHE_DRIVER` em vez de `CACHE_STORE`)

## Alterações Realizadas

### 1. `env` - Variáveis de Ambiente
```env
BROADCAST_DRIVER=reverb
CACHE_STORE=redis
CACHE_DRIVER=redis
FILESYSTEM_DISK=local
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
SESSION_LIFETIME=120

REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379
```

### 2. `config/database.php` - Configuração Redis
- ❌ **Removido**: `'scheme' => env('REDIS_SCHEME', 'tls')` (causava erro)
- ✅ **Ajustado**: Configuração padrão sem TLS
- ✅ **Adicionado**: `'database' => env('REDIS_DB', '0')` para conexão default
- ✅ **Adicionado**: `'database' => env('REDIS_CACHE_DB', '1')` para cache

### 3. `config/cache.php`
- ✅ Mantido: `'default' => env('CACHE_STORE', 'redis')`

## Configuração Atual

### Containers Docker Ativos
```bash
✅ backend-redis-1        redis:alpine    (healthy)   0.0.0.0:6379->6379/tcp
✅ backend-laravel.test-1 sail-8.3/app    (running)   
✅ backend-mysql-1        mysql:8.0.32    (healthy)
✅ backend-reverb-1       sail-8.3/app    (running)
```

### Drivers Configurados
- **Cache**: Redis (database 1)
- **Session**: Redis
- **Queue**: Redis
- **Broadcast**: Reverb (usando Redis internamente)

### Bancos de Dados Redis
- **0**: Conexão default
- **1**: Cache
- **2-15**: Disponíveis para uso futuro

## Teste de Verificação

### Comando de Teste
```bash
php artisan tinker --execute="use Illuminate\Support\Facades\Cache; Cache::put('test', 'OK', 60); echo Cache::get('test');"
```

### Resultado Esperado
```
OK
```

## Status do Redis

### Verificar Conexão
```bash
# Via Docker
docker exec -it backend-redis-1 redis-cli ping
# Deve retornar: PONG

# Ver todas as chaves
docker exec -it backend-redis-1 redis-cli KEYS '*'

# Monitorar em tempo real
docker exec -it backend-redis-1 redis-cli MONITOR
```

### Estatísticas
```bash
docker exec -it backend-redis-1 redis-cli INFO stats
```

## Comandos Úteis

### Limpar Cache Redis
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### Recriar Cache
```bash
php artisan config:cache
php artisan route:cache
```

### Limpar Todo Redis (CUIDADO!)
```bash
docker exec -it backend-redis-1 redis-cli FLUSHALL
```

## Benefícios da Configuração Atual

### Performance
- ✅ Cache rápido em memória
- ✅ Sessions persistentes entre requisições
- ✅ Filas assíncronas para processos pesados
- ✅ Broadcasting em tempo real via Reverb

### Escalabilidade
- ✅ Pronto para clustering
- ✅ Suporte a múltiplos workers
- ✅ Cache distribuído

### Desenvolvimento
- ✅ Fácil debug com redis-cli
- ✅ Isolamento de dados por database
- ✅ Reset rápido de cache

## Para Produção

### DigitalOcean
Adicionar ao `.env` de produção:
```env
CACHE_STORE=redis
REDIS_HOST=seu-redis-host.digitalocean.com
REDIS_PASSWORD=sua-senha-segura
REDIS_PORT=6379
REDIS_CLIENT=predis
```

### Segurança
1. Sempre usar senha forte no Redis de produção
2. Habilitar TLS/SSL se disponível
3. Limitar acesso por IP
4. Usar databases separados por ambiente

## Status Final
- ✅ Redis configurado e funcionando
- ✅ Cache operacional
- ✅ Sessions usando Redis
- ✅ Queues usando Redis
- ✅ Broadcast via Reverb funcionando
