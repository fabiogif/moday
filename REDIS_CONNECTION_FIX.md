# Redis Connection Fix - Documentação

## Problema Identificado

O backend Laravel estava tentando conectar ao Redis usando o hostname `redis`, que só funciona dentro da rede Docker. Como o backend estava rodando via `php artisan serve` (fora do Docker), não conseguia resolver o hostname `redis`, resultando em erro:

```
Error: php_network_getaddresses: getaddrinfo for redis failed: nodename nor servname provided, or not known [tcp://redis:6379]
```

## Solução Aplicada

### 1. Atualização do arquivo `.env`

Alterados os seguintes valores para usar `127.0.0.1` ao invés de hostnames Docker:

**Antes:**
```env
DB_HOST=mysql
REDIS_HOST=redis
MAIL_HOST=mailpit
```

**Depois:**
```env
DB_HOST=127.0.0.1
REDIS_HOST=127.0.0.1
MAIL_HOST=127.0.0.1
```

### 2. Melhoria no CacheService

Refatorado o método `invalidateCacheByPattern` para usar `SCAN` ao invés de `KEYS`:

**Benefícios:**
- Melhor performance (SCAN não bloqueia o Redis)
- Tratamento de erros mais robusto
- Compatibilidade com diferentes drivers de cache

**Código atualizado:**
```php
private function invalidateCacheByPattern(string $pattern): void
{
    try {
        $store = Cache::getStore();
        
        if (method_exists($store, 'getRedis')) {
            $redis = $store->getRedis();
            
            $cursor = '0';
            do {
                $result = $redis->scan($cursor, ['MATCH' => $pattern, 'COUNT' => 100]);
                $cursor = $result[0];
                $keys = $result[1] ?? [];
                
                if (!empty($keys)) {
                    foreach ($keys as $key) {
                        Cache::forget(str_replace(config('cache.prefix') . ':', '', $key));
                    }
                }
            } while ($cursor !== '0');
        }
    } catch (\Exception $e) {
        Log::warning("Failed to invalidate cache pattern: {$pattern}", [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
    }
}
```

## Configuração do Ambiente

### Docker Services Ativos

```bash
# Redis rodando no Docker (porta 6379)
docker ps | grep redis
# Output: backend-redis-1 (0.0.0.0:6379->6379/tcp)
```

### Backend Rodando Localmente

```bash
# Backend Laravel rodando fora do Docker
php artisan serve --port=8000
```

### Configuração Redis

```env
CACHE_DRIVER=redis
SESSION_DRIVER=redis
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_CLIENT=predis
```

## Testes Realizados

### 1. Teste de Conexão Redis

```bash
php artisan tinker --execute="
    use Illuminate\Support\Facades\Redis; 
    Redis::set('test', 'working'); 
    echo Redis::get('test');
"
# Output: working ✅
```

### 2. Teste de Login

```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"email":"fabio@fabio.com","password":"123456"}'
# Output: {"success":true,...} ✅
```

### 3. Teste de Dashboard Metrics

```bash
curl -X GET http://localhost:8000/api/dashboard/metrics \
  -H "Authorization: Bearer {token}"
# Output: {"success":true,"data":{...}} ✅
```

## Rotas Dashboard Disponíveis

Todas as rotas de dashboard estão funcionando corretamente:

```
GET  /api/dashboard/metrics              - Métricas gerais
GET  /api/dashboard/sales-performance    - Desempenho de vendas
GET  /api/dashboard/recent-transactions  - Transações recentes
GET  /api/dashboard/top-products         - Principais produtos
POST /api/dashboard/clear-cache          - Limpar cache
```

## Endpoints de Autenticação

```
POST /api/auth/login           - Login
POST /api/auth/logout          - Logout
POST /api/auth/register        - Registro
POST /api/auth/refresh         - Refresh token
GET  /api/auth/me              - Dados do usuário
POST /api/auth/forgot-password - Esqueci a senha
POST /api/auth/reset-password  - Resetar senha
```

## Cache TTL Configurado

O `CacheService` está configurado com os seguintes TTLs:

- **Dashboard Metrics**: 5 minutos (300s)
- **Sales Performance**: 10 minutos (600s)
- **Recent Transactions**: 5 minutos (300s)
- **Top Products**: 10 minutos (600s)
- **Client Stats**: 30 minutos (1800s)
- **Product Stats**: 30 minutos (1800s)
- **Order Stats**: 15 minutos (900s)

## Limpeza de Cache

Para limpar o cache do dashboard:

```bash
# Via API
curl -X POST http://localhost:8000/api/dashboard/clear-cache \
  -H "Authorization: Bearer {token}"

# Via Artisan
php artisan cache:clear
```

## Comandos Úteis

```bash
# Limpar e reconstruir cache de configuração
php artisan config:clear && php artisan config:cache

# Verificar status do Redis
redis-cli ping

# Monitorar comandos Redis
redis-cli monitor

# Verificar chaves em cache
redis-cli --scan --pattern "laravel_database_*"
```

## Arquivos Modificados

1. `/backend/.env` - Atualizado hosts para 127.0.0.1
2. `/backend/app/Services/CacheService.php` - Melhorado método de invalidação por padrão

## Próximos Passos

Para melhorar ainda mais o sistema de cache:

1. **Implementar Cache Tags** (Redis 6.0+)
2. **Adicionar monitoramento de hit/miss rate**
3. **Implementar cache warming para dados críticos**
4. **Adicionar circuit breaker para falhas de Redis**
5. **Configurar Redis Sentinel para HA**

## Observações Importantes

1. **Docker vs Local**: Sempre use `127.0.0.1` quando o backend rodar localmente e `redis` quando rodar dentro do Docker
2. **Predis vs PhpRedis**: O projeto usa `predis/predis` (client PHP puro), alternativa seria `phpredis` (extensão C)
3. **Cache Prefix**: Todas as chaves têm prefixo `laravel_database_` configurado em `config/database.php`
4. **Session Storage**: Sessions também estão armazenadas no Redis (DB 0)

## Troubleshooting

### Erro: "Class Redis not found"
- Certifique-se que `predis/predis` está instalado: `composer require predis/predis`

### Erro: "Connection refused"
- Verifique se Redis está rodando: `docker ps | grep redis`
- Verifique se a porta 6379 está acessível: `telnet 127.0.0.1 6379`

### Erro: "NOAUTH Authentication required"
- Configure `REDIS_PASSWORD` no `.env`

### Cache não atualiza
- Limpe o cache: `php artisan cache:clear`
- Verifique TTL configurado em `CacheService::CACHE_TTL`
