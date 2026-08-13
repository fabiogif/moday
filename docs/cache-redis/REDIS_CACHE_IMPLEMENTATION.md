# Implementação de Cache Redis - Documentação

## 📋 Resumo

Este documento detalha a implementação completa do sistema de cache Redis no projeto, seguindo as melhores práticas do Laravel.

## 🔧 Configuração

### Docker Compose

O Redis está configurado no `docker-compose.yml`:

```yaml
redis:
    image: 'redis:alpine'
    ports:
        - '${FORWARD_REDIS_PORT:-6379}:6379'
    volumes:
        - 'sail-redis:/data'
    networks:
        - sail
    healthcheck:
        test:
            - CMD
            - redis-cli
            - ping
        retries: 3
        timeout: 5s
```

### Configuração do Laravel

**Arquivo `.env`:**
```env
CACHE_DRIVER=redis
SESSION_DRIVER=redis
REDIS_HOST=redis
REDIS_PASSWORD=null
REDIS_PORT=6379
REDIS_CLIENT=phpredis
```

## 📦 Estrutura do Cache

### CacheService

Localizado em `app/Services/CacheService.php`, o serviço centraliza toda a lógica de cache com:

#### TTL (Time To Live) Configurado

- **Dashboard Metrics**: 5 minutos (300s)
- **Dashboard Revenue**: 5 minutos (300s)
- **Sales Performance**: 10 minutos (600s)
- **Recent Transactions**: 5 minutos (300s)
- **Top Products**: 10 minutos (600s)
- **Order Stats**: 15 minutos (900s)
- **Order List**: 10 minutos (600s)
- **Product Stats**: 30 minutos (1800s)
- **Product List**: 15 minutos (900s)
- **Client Stats**: 30 minutos (1800s)
- **Client List**: 15 minutos (900s)
- **Category Stats**: 1 hora (3600s)
- **Category List**: 30 minutos (1800s)
- **Table Stats**: 1 hora (3600s)
- **Table List**: 30 minutos (1800s)
- **User List**: 20 minutos (1200s)
- **Profile List**: 1 hora (3600s)
- **Permission List**: 2 horas (7200s)

#### Métodos Principais

```php
// Recuperar com cache
$this->cacheService->getDashboardMetrics($tenantId, function() {
    // Lógica para buscar dados
    return $data;
});

// Invalidar cache específico
$this->cacheService->invalidateDashboardCache($tenantId);

// Invalidar todo cache do tenant
$this->cacheService->invalidateAllTenantCache($tenantId);
```

## 🔄 Invalidação Automática de Cache

### Observers

Observers foram implementados para invalidar automaticamente o cache quando os dados são modificados:

#### OrderObserver

```php
// app/Observers/OrderObserver.php
// Invalida cache em: created, updated, deleted, restored, forceDeleted
```

Registrado em `AppServiceProvider`:
```php
Order::observe(OrderObserver::class);
```

### Invalidação Manual

Os Services invalidam o cache após operações de escrita:

**Exemplo em OrderService:**
```php
public function createNewOrder(array $order)
{
    // ... criação do pedido
    
    // Invalidar cache
    $this->cacheService->invalidateOrderCache($tenantId);
    
    return $order;
}
```

## 🎯 Endpoints Implementados

### Dashboard Metrics

```
GET /api/dashboard/metrics
GET /api/dashboard/sales-performance
GET /api/dashboard/recent-transactions
GET /api/dashboard/top-products
GET /api/dashboard/realtime-updates
POST /api/dashboard/clear-cache
```

Todos os endpoints utilizam cache com invalidação automática.

## 🛠️ Comandos Artisan

### Testar Cache

```bash
./vendor/bin/sail artisan cache:test
```

Saída esperada:
```
Testing Redis Cache Connection...

1. Testing Redis Connection...
✓ Redis connection successful!

2. Testing Cache Store...
✓ Cached: test_key_xxx = test_value_xxx
✓ Retrieved: test_value_xxx
✓ Cache key deleted

3. Cache Configuration:
Cache Driver: redis
Redis Host: redis
Redis Port: 6379
Redis Client: phpredis

4. Redis Info:
Redis Version: 8.2.2
Connected Clients: 3
Used Memory: 1.13M

✓ All cache tests passed successfully!
```

### Limpar Cache do Tenant

```bash
# Limpar cache de um tenant específico
./vendor/bin/sail artisan cache:clear-tenant 1

# Limpar todo o cache
./vendor/bin/sail artisan cache:clear-tenant
```

### Comandos Padrão do Laravel

```bash
# Limpar cache da aplicação
./vendor/bin/sail artisan cache:clear

# Limpar cache de configuração
./vendor/bin/sail artisan config:clear

# Limpar cache de rotas
./vendor/bin/sail artisan route:clear
```

## 📊 Monitoramento

### Verificar Status do Redis

```bash
# Via Docker
./vendor/bin/sail ps | grep redis

# Conectar ao Redis CLI
./vendor/bin/sail redis redis-cli

# Comandos úteis no Redis CLI
> PING  # Testar conexão
> KEYS *  # Listar todas as chaves
> INFO  # Informações do servidor
> DBSIZE  # Número de chaves
```

### Logs

O CacheService inclui tratamento de erros e logging:

```php
try {
    return Cache::remember($key, $ttl, $callback);
} catch (\Exception $e) {
    Log::error("Cache error for key {$key}: " . $e->getMessage());
    return $callback();  // Fallback sem cache
}
```

## 🔐 Segurança e Boas Práticas

### 1. **Isolamento por Tenant**
Todas as chaves de cache incluem o `tenant_id` para evitar vazamento de dados entre tenants.

### 2. **Fallback Gracioso**
Se o Redis falhar, o sistema continua funcionando sem cache (dados são buscados diretamente do banco).

### 3. **Invalidação Inteligente**
Cache é invalidado automaticamente quando:
- Pedidos são criados/atualizados/deletados
- Produtos são modificados
- Clientes são atualizados
- Qualquer dado relacionado ao dashboard é alterado

### 4. **TTL Estratégico**
- Dados frequentemente acessados: TTL curto (5-10 min)
- Dados estáticos: TTL longo (1-2 horas)
- Dados em tempo real: Invalidação via WebSocket/Observer

## 🚀 Controllers Refatorados

### DashboardMetricsController

✅ Todos os métodos usam cache:
- `getMetricsOverview()` - Cache de 5 minutos
- `getSalesPerformance()` - Cache de 10 minutos
- `getRecentTransactions()` - Cache de 5 minutos
- `getTopProducts()` - Cache de 10 minutos
- `clearCache()` - Endpoint para invalidação manual

## 📈 Performance

### Ganhos Esperados

- **Redução de carga no banco**: 70-90%
- **Tempo de resposta**: Redução de 80-95%
- **Escalabilidade**: Suporte a muito mais requisições simultâneas

### Exemplo de Desempenho

**Sem Cache:**
- Query complexa: ~500-800ms
- Múltiplas queries: ~2-5s

**Com Cache:**
- Primeira requisição: ~500-800ms (miss)
- Requisições subsequentes: ~5-20ms (hit)

## 🔄 Atualização e Manutenção

### Adicionar Novo Cache

1. Adicionar TTL no `CacheService`:
```php
private const CACHE_TTL = [
    'meu_novo_cache' => 600, // 10 minutos
];
```

2. Criar método no `CacheService`:
```php
public function getMeuNovoCache(int $tenantId, callable $callback)
{
    $cacheKey = "meu_novo_cache_{$tenantId}";
    $ttl = self::CACHE_TTL['meu_novo_cache'];
    return $this->remember($cacheKey, $ttl, $callback);
}
```

3. Adicionar invalidação:
```php
public function invalidateMeuNovoCache(int $tenantId): void
{
    Cache::forget("meu_novo_cache_{$tenantId}");
}
```

4. Usar no Controller:
```php
$data = $this->cacheService->getMeuNovoCache($tenantId, function() {
    return $this->buscarDados();
});
```

## 🐛 Troubleshooting

### Redis não conecta

```bash
# Verificar se está rodando
./vendor/bin/sail ps

# Reiniciar Redis
./vendor/bin/sail restart redis

# Ver logs
./vendor/bin/sail logs redis
```

### Cache não invalida

```bash
# Limpar manualmente
./vendor/bin/sail artisan cache:clear

# Verificar observers
./vendor/bin/sail artisan tinker
> App\Models\Order::getObservableEvents()
```

### Problemas de memória

```bash
# Conectar ao Redis
./vendor/bin/sail redis redis-cli

# Ver uso de memória
> INFO memory

# Limpar tudo (cuidado!)
> FLUSHALL
```

## 📝 Checklist de Implementação

✅ Redis configurado no Docker Compose
✅ Variáveis de ambiente configuradas (.env)
✅ CacheService implementado com TTL estratégico
✅ Observers criados para invalidação automática
✅ Controllers refatorados para usar cache
✅ Comandos Artisan para teste e manutenção
✅ Endpoint de invalidação manual
✅ Logs e tratamento de erros
✅ Documentação completa

## 🎓 Referências

- [Laravel Cache Documentation](https://laravel.com/docs/11.x/cache)
- [Redis Documentation](https://redis.io/docs/)
- [Laravel Redis Documentation](https://laravel.com/docs/11.x/redis)
- [Cache Best Practices](https://laravel.com/docs/11.x/cache#cache-tags)
