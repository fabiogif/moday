# Sistema de Cache Redis - Guia Rápido de Uso

## 🚀 Início Rápido

### 1. Verificar se Redis está funcionando

```bash
cd backend
./vendor/bin/sail artisan cache:test
```

Resultado esperado:
```
✓ Redis connection successful!
✓ Cached and retrieved successfully
```

### 2. Limpar Cache

```bash
# Limpar todo o cache
./vendor/bin/sail artisan cache:clear

# Limpar cache de um tenant específico
./vendor/bin/sail artisan cache:clear-tenant 1
```

### 3. Testar o Sistema

```bash
./test-cache.sh
```

## 📡 Endpoints da API

### Dashboard com Cache

#### 1. Métricas Gerais
```http
GET /api/dashboard/metrics
Authorization: Bearer {token}
```

**Cache:** 5 minutos

**Resposta:**
```json
{
  "data": {
    "total_revenue": {
      "value": 15000.00,
      "formatted": "R$ 15.000,00",
      "growth": 12.5,
      "trend": "up",
      "subtitle": "Tendência em alta neste mês",
      "description": "Receita dos últimos 6 meses",
      "chart_data": [...]
    },
    "active_clients": {
      "value": 150,
      "growth": 105.2,
      "trend": "up",
      "subtitle": "Forte retenção de usuários",
      "description": "O engajamento excede as metas"
    },
    "total_orders": {
      "value": 320,
      "growth": -2.0,
      "trend": "down",
      "subtitle": "Queda de 2% neste período",
      "description": "O volume de pedidos precisa de atenção"
    },
    "conversion_rate": {
      "value": 45.5,
      "formatted": "45.5%",
      "growth": 8.2,
      "trend": "up",
      "subtitle": "Aumento constante do desempenho",
      "description": "Atende às projeções de conversão"
    }
  }
}
```

#### 2. Desempenho de Vendas
```http
GET /api/dashboard/sales-performance
Authorization: Bearer {token}
```

**Cache:** 10 minutos

**Resposta:**
```json
{
  "data": {
    "monthly_data": [
      {
        "month": "Jan/25",
        "sales": 12000.00,
        "goal": 14400.00,
        "orders": 180,
        "performance": 83.3
      }
    ],
    "current_month": {...},
    "summary": {
      "total_sales": 120000.00,
      "total_goal": 144000.00,
      "avg_performance": 85.5
    }
  }
}
```

#### 3. Transações Recentes
```http
GET /api/dashboard/recent-transactions
Authorization: Bearer {token}
```

**Cache:** 5 minutos

**Resposta:**
```json
{
  "data": {
    "transactions": [
      {
        "id": 123,
        "identify": "2iqpg6j8",
        "client": {
          "name": "João Silva",
          "email": "joao@example.com"
        },
        "table": "Mesa 01",
        "total": 150.00,
        "formatted_total": "R$ 150,00",
        "status": "Entregue",
        "payment_method": "Cartão de Crédito",
        "created_at": "06/01/2025 14:30",
        "created_at_human": "há 2 horas"
      }
    ],
    "total": 10
  }
}
```

#### 4. Principais Produtos
```http
GET /api/dashboard/top-products
Authorization: Bearer {token}
```

**Cache:** 10 minutos

**Resposta:**
```json
{
  "data": {
    "products": [
      {
        "rank": 1,
        "id": 45,
        "uuid": "abc-123",
        "name": "Pizza Margherita",
        "image": "products/pizza.jpg",
        "price": 45.00,
        "formatted_price": "R$ 45,00",
        "total_quantity": 85,
        "total_revenue": 3825.00,
        "formatted_revenue": "R$ 3.825,00",
        "orders_count": 65
      }
    ],
    "total_products": 10,
    "total_revenue": 25000.00,
    "formatted_total_revenue": "R$ 25.000,00"
  }
}
```

#### 5. Atualizações em Tempo Real
```http
GET /api/dashboard/realtime-updates
Authorization: Bearer {token}
```

**Sem cache** (sempre atualizado)

**Resposta:**
```json
{
  "data": {
    "recent_orders": 5,
    "recent_revenue": 850.00,
    "timestamp": "2025-01-06T14:30:00.000Z",
    "channel": "tenant.1.dashboard"
  }
}
```

#### 6. Limpar Cache do Dashboard
```http
POST /api/dashboard/clear-cache
Authorization: Bearer {token}
```

**Resposta:**
```json
{
  "success": true,
  "message": "Cache do dashboard limpo com sucesso",
  "data": {
    "tenant_id": 1
  }
}
```

## 🔄 Invalidação Automática

O cache é **automaticamente invalidado** quando:

### Pedidos (Orders)
- ✅ Novo pedido criado
- ✅ Pedido atualizado (status, produtos, etc)
- ✅ Pedido cancelado/deletado

**Caches invalidados:**
- `order_list_{tenant_id}`
- `order_stats_{tenant_id}`
- `dashboard_metrics_{tenant_id}`
- `dashboard_revenue_{tenant_id}`
- `recent_transactions_{tenant_id}`
- `sales_performance_{tenant_id}`
- `top_products_{tenant_id}`

### Produtos (Products)
- ✅ Produto criado
- ✅ Produto atualizado
- ✅ Produto deletado

**Caches invalidados:**
- `product_list_{tenant_id}`
- `product_stats_{tenant_id}`
- `top_products_{tenant_id}`

### Clientes (Clients)
- ✅ Cliente criado
- ✅ Cliente atualizado
- ✅ Cliente deletado

**Caches invalidados:**
- `client_list_{tenant_id}`
- `client_stats_{tenant_id}`
- `dashboard_metrics_{tenant_id}`

## 💡 Exemplos de Uso no Frontend

### React/Next.js

```typescript
// Hook personalizado para dashboard metrics
import { useQuery } from '@tanstack/react-query'

export function useDashboardMetrics() {
  return useQuery({
    queryKey: ['dashboard', 'metrics'],
    queryFn: async () => {
      const response = await fetch('/api/dashboard/metrics', {
        headers: {
          Authorization: `Bearer ${token}`
        }
      })
      return response.json()
    },
    // O cache do frontend pode ser menor que o do backend
    staleTime: 2 * 60 * 1000, // 2 minutos
    cacheTime: 5 * 60 * 1000, // 5 minutos
  })
}

// Invalidar cache manualmente quando necessário
import { useMutation, useQueryClient } from '@tanstack/react-query'

export function useClearDashboardCache() {
  const queryClient = useQueryClient()
  
  return useMutation({
    mutationFn: async () => {
      const response = await fetch('/api/dashboard/clear-cache', {
        method: 'POST',
        headers: {
          Authorization: `Bearer ${token}`
        }
      })
      return response.json()
    },
    onSuccess: () => {
      // Invalidar queries do React Query
      queryClient.invalidateQueries(['dashboard'])
    }
  })
}
```

### Componente de Dashboard

```typescript
import { useDashboardMetrics } from '@/hooks/useDashboardMetrics'

export function DashboardMetrics() {
  const { data, isLoading, error } = useDashboardMetrics()

  if (isLoading) return <LoadingSpinner />
  if (error) return <ErrorMessage error={error} />

  return (
    <div className="grid grid-cols-4 gap-4">
      {/* Card Receita Total */}
      <MetricCard
        title="Receita Total"
        value={data.total_revenue.formatted}
        growth={data.total_revenue.growth}
        trend={data.total_revenue.trend}
        subtitle={data.total_revenue.subtitle}
        description={data.total_revenue.description}
      />

      {/* Card Clientes Ativos */}
      <MetricCard
        title="Clientes Ativos"
        value={data.active_clients.value}
        growth={data.active_clients.growth}
        trend={data.active_clients.trend}
        subtitle={data.active_clients.subtitle}
        description={data.active_clients.description}
      />

      {/* Card Total de Pedidos */}
      <MetricCard
        title="Total de Pedidos"
        value={data.total_orders.value}
        growth={data.total_orders.growth}
        trend={data.total_orders.trend}
        subtitle={data.total_orders.subtitle}
        description={data.total_orders.description}
      />

      {/* Card Taxa de Conversão */}
      <MetricCard
        title="Taxa de Conversão"
        value={data.conversion_rate.formatted}
        growth={data.conversion_rate.growth}
        trend={data.conversion_rate.trend}
        subtitle={data.conversion_rate.subtitle}
        description={data.conversion_rate.description}
      />
    </div>
  )
}
```

## 🐛 Troubleshooting

### Cache não está funcionando

1. Verificar Redis:
```bash
./vendor/bin/sail ps | grep redis
# Deve mostrar: healthy
```

2. Testar conexão:
```bash
./vendor/bin/sail artisan cache:test
```

3. Ver logs:
```bash
./vendor/bin/sail logs redis
./vendor/bin/sail logs laravel.test
```

### Dados desatualizados

1. Limpar cache manualmente:
```bash
./vendor/bin/sail artisan cache:clear
```

2. Ou via API:
```bash
curl -X POST http://localhost/api/dashboard/clear-cache \
  -H "Authorization: Bearer {token}"
```

### Redis com muita memória

```bash
# Conectar ao Redis
./vendor/bin/sail redis redis-cli

# Ver uso de memória
> INFO memory

# Limpar tudo (CUIDADO!)
> FLUSHALL
```

## 📊 Monitoramento

### Verificar chaves no Redis

```bash
./vendor/bin/sail redis redis-cli

# Listar todas as chaves
> KEYS *

# Ver valor de uma chave específica
> GET dashboard_metrics_1

# Ver TTL de uma chave
> TTL dashboard_metrics_1

# Contar chaves
> DBSIZE
```

### Estatísticas do Redis

```bash
./vendor/bin/sail redis redis-cli INFO

# Ou via comando customizado
./vendor/bin/sail artisan cache:test
```

## ✅ Checklist de Produção

Antes de colocar em produção, verificar:

- [ ] Redis está rodando e saudável
- [ ] Variáveis de ambiente configuradas
- [ ] TTLs ajustados conforme necessidade
- [ ] Observers registrados
- [ ] Monitoramento configurado
- [ ] Backup do Redis configurado (se necessário)
- [ ] Rate limiting configurado nos endpoints

## 🔗 Links Úteis

- [Documentação Completa](./REDIS_CACHE_IMPLEMENTATION.md)
- [Resumo da Refatoração](./REFATORACAO_CACHE_RESUMO.md)
- [Laravel Cache Docs](https://laravel.com/docs/11.x/cache)
- [Redis Docs](https://redis.io/docs/)
