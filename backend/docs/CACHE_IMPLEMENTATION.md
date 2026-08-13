# 🚀 Implementação de Cache com Redis

Este documento descreve a implementação de cache para estatísticas e dados da aplicação usando Redis.

## 📋 Visão Geral

O sistema de cache foi implementado para melhorar a performance da aplicação, especialmente para:

-   Estatísticas de clientes
-   Estatísticas de produtos
-   Estatísticas de pedidos
-   Dados do dashboard
-   Dados de pedidos individuais

## 🏗️ Arquitetura

### Serviços Principais

1. **CacheService** - Serviço central para gerenciamento de cache
2. **ListingCacheService** - Serviço especializado para cache de listagens
3. **ClientService** - Cache para estatísticas e listagens de clientes
4. **ProductService** - Cache para estatísticas e listagens de produtos
5. **OrderService** - Cache para estatísticas e listagens de pedidos
6. **CategoryService** - Cache para listagens de categorias
7. **TableService** - Cache para listagens de mesas
8. **DashboardService** - Cache para dados consolidados do dashboard

### Configuração de TTL

```php
'client_stats' => 30,      // 30 minutos
'product_stats' => 30,     // 30 minutos
'order_stats' => 15,       // 15 minutos
'category_stats' => 60,    // 1 hora
'table_stats' => 60,       // 1 hora
'order_data' => 10,       // 10 minutos
'dashboard_data' => 20,    // 20 minutos
// Cache para listagens
'client_list' => 15,       // 15 minutos
'product_list' => 15,      // 15 minutos
'order_list' => 10,       // 10 minutos
'category_list' => 30,     // 30 minutos
'table_list' => 30,       // 30 minutos
'user_list' => 20,        // 20 minutos
'profile_list' => 60,     // 1 hora
'permission_list' => 120, // 2 horas
'role_list' => 60,        // 1 hora
```

## 🔧 Uso

### Endpoints com Cache

#### Estatísticas de Clientes

```http
GET /api/client/stats
```

#### Estatísticas de Pedidos

```http
GET /api/order/stats
```

#### Dashboard Completo

```http
GET /api/dashboard
```

### Invalidação Automática

O cache é invalidado automaticamente quando:

-   Clientes são criados, atualizados ou deletados
-   Produtos são criados, atualizados ou deletados
-   Pedidos são criados, atualizados ou deletados
-   Categorias são modificadas
-   Mesas são modificadas

## 🛠️ Comandos Artisan

### Limpar Todo o Cache

```bash
php artisan cache:manage clear
```

### Ver Estatísticas do Cache

```bash
php artisan cache:manage stats
```

### Invalidar Cache de um Tenant

```bash
# Invalidar todo cache de um tenant
php artisan cache:manage invalidate --tenant=1 --type=all

# Invalidar apenas cache de clientes
php artisan cache:manage invalidate --tenant=1 --type=client

# Invalidar apenas cache de produtos
php artisan cache:manage invalidate --tenant=1 --type=product

# Invalidar apenas cache de pedidos
php artisan cache:manage invalidate --tenant=1 --type=order
```

## 🧪 Testes

Execute os testes de cache:

```bash
php artisan test tests/Feature/CacheTest.php
```

## 📊 Monitoramento

### Verificar Status do Redis

```bash
# Verificar se Redis está funcionando
redis-cli ping

# Ver chaves de cache
redis-cli keys "*cache*"

# Ver estatísticas do Redis
redis-cli info memory
```

### Logs de Cache

Os logs de cache são registrados em:

-   `storage/logs/laravel.log`
-   Procure por mensagens de cache invalidation

## 🔍 Debugging

### Verificar Cache no Código

```php
use App\Services\CacheService;

$cacheService = app(CacheService::class);

// Verificar se uma chave existe
if (Cache::has("client_stats_1")) {
    echo "Cache exists!";
}

// Ver estatísticas do cache
$stats = $cacheService->getCacheStats();
dd($stats);
```

### Limpar Cache Específico

```php
use App\Services\CacheService;

$cacheService = app(CacheService::class);

// Invalidar cache de um tenant específico
$cacheService->invalidateAllTenantCache(1);

// Invalidar cache de clientes
$cacheService->invalidateClientCache(1);
```

## ⚡ Performance

### Benefícios Esperados

-   **Redução de 70-90%** no tempo de resposta para estatísticas
-   **Menor carga** no banco de dados
-   **Melhor experiência** do usuário
-   **Escalabilidade** melhorada

### Métricas de Cache

-   **Hit Rate**: Percentual de requisições servidas pelo cache
-   **Miss Rate**: Percentual de requisições que precisam buscar no banco
-   **TTL**: Tempo de vida dos dados em cache

## 🚨 Troubleshooting

### Cache Não Funciona

1. Verificar se Redis está rodando:

    ```bash
    docker-compose ps redis
    ```

2. Verificar configuração do cache:

    ```bash
    php artisan config:cache
    ```

3. Limpar cache de configuração:
    ```bash
    php artisan config:clear
    ```

### Performance Lenta

1. Verificar se Redis tem memória suficiente
2. Ajustar TTL dos caches se necessário
3. Verificar se invalidação está funcionando corretamente

### Dados Desatualizados

1. Verificar se invalidação automática está funcionando
2. Limpar cache manualmente se necessário
3. Verificar logs de invalidação

## 📈 Próximos Passos

1. **Cache de Consultas**: Implementar cache para consultas complexas
2. **Cache Distribuído**: Configurar cache para múltiplos servidores
3. **Métricas Avançadas**: Implementar monitoramento detalhado
4. **Cache Warming**: Pré-carregar cache com dados importantes
5. **Cache Tags**: Implementar invalidação por tags

## 🔗 Referências

-   [Laravel Cache Documentation](https://laravel.com/docs/cache)
-   [Redis Documentation](https://redis.io/documentation)
-   [Laravel Redis Configuration](https://laravel.com/docs/redis)
