# 🚀 Guia de Início Rápido - Implementação de Cache

## ⚡ 5 Minutos para Começar

### 1. Verificar Redis (30 segundos)

```bash
# Verificar se Redis está rodando
docker ps | grep redis

# Se não estiver, iniciar
docker-compose up -d redis

# Testar conexão
docker exec backend_moday-redis-1 redis-cli ping
# Deve retornar: PONG
```

### 2. Analisar Estado Atual (1 minuto)

```bash
# Executar comando de análise
php artisan cache:analyze

# Ou via Docker
docker exec backend_moday-laravel.test-1 php artisan cache:analyze
```

**O que você verá:**
- ✅ Status do Redis
- 📊 Estatísticas (hits, misses, hit rate)
- 🔍 Controllers com/sem cache
- 🔑 Chaves existentes

### 3. Implementar Seu Primeiro Cache (3 minutos)

#### Exemplo: ProductApiController

**Antes:**
```php
public function index(): JsonResponse
{
    $data = $this->productService->index();
    return ApiResponseClass::sendResponse($data, '', 200);
}
```

**Depois:**
```php
use Illuminate\Support\Facades\Cache;

public function index(): JsonResponse
{
    $tenantId = auth()->user()->tenant_id;
    
    // Adicionar cache
    $data = Cache::remember("products_list_{$tenantId}", 900, function() {
        return $this->productService->index();
    });
    
    return ApiResponseClass::sendResponse($data, '', 200);
}
```

**Mudanças:**
1. ✅ Adicionou `use Illuminate\Support\Facades\Cache;`
2. ✅ Envolveu query em `Cache::remember()`
3. ✅ Chave única: `"products_list_{$tenantId}"`
4. ✅ TTL: `900` segundos (15 minutos)

---

## 📝 Template Copy-Paste

### Para Listagens (index)

```php
public function index(Request $request): JsonResponse
{
    $tenantId = auth()->user()->tenant_id;
    $page = $request->get('page', 1);
    
    $cacheKey = "MODULE_list_{$tenantId}_page_{$page}";
    
    $data = Cache::remember($cacheKey, 900, function() use ($tenantId, $request) {
        return $this->moduleService->paginate(
            page: $request->get('page', 1),
            totalPerPage: $request->get('per_page', 15),
            filter: $request->filter ?? '',
            tenantId: $tenantId
        );
    });
    
    return ApiResponseClass::sendResponsePaginate(
        ModuleResource::class,
        $data,
        200
    );
}
```

### Para Estatísticas (stats)

```php
use App\Services\CacheService;

public function __construct(
    private readonly ModuleService $moduleService,
    private readonly CacheService $cacheService
) {}

public function stats(): JsonResponse
{
    $tenantId = auth()->user()->tenant_id;
    
    $stats = $this->cacheService->getModuleStats(
        $tenantId,
        fn() => $this->moduleService->getStats($tenantId)
    );
    
    return ApiResponseClass::sendResponse($stats, '', 200);
}
```

### Para Invalidação (store/update/delete)

```php
public function store(Request $request): JsonResponse
{
    $tenantId = auth()->user()->tenant_id;
    
    $item = $this->moduleService->store($data);
    
    // IMPORTANTE: Invalidar cache
    $this->cacheService->invalidateModuleCache($tenantId);
    
    return ApiResponseClass::sendResponse(
        new ModuleResource($item),
        'Criado com sucesso',
        201
    );
}
```

---

## 🎯 Implementação por Prioridade

### Alta Prioridade (Fazer HOJE)

1. **ProductApiController** - Produtos são consultados constantemente
   ```
   ├─ index() - listagem
   ├─ stats() - estatísticas
   ├─ show() - detalhes
   └─ store/update/delete() - invalidação
   ```

2. **DashboardMetricsController** - Dashboard é acessado frequentemente
   ```
   ├─ getMetricsOverview()
   ├─ getTopProducts()
   └─ getSalesPerformance()
   ```

3. **OrderApiController** - Pedidos afetam performance
   ```
   ├─ index() - listagem
   ├─ stats() - estatísticas
   └─ store/update() - invalidação
   ```

### Média Prioridade (Esta Semana)

4. **CategoryApiController**
5. **ClientApiController**
6. **TableApiController**

### Baixa Prioridade (Quando Possível)

7. **UserApiController**
8. **PermissionApiController**
9. **RoleApiController**

---

## 🧪 Testando

### Teste Básico (2 minutos)

```bash
# 1. Primeira requisição (cache miss)
time curl http://localhost:8000/api/product \
  -H "Authorization: Bearer YOUR_TOKEN"

# Tempo esperado: ~200ms

# 2. Segunda requisição (cache hit)
time curl http://localhost:8000/api/product \
  -H "Authorization: Bearer YOUR_TOKEN"

# Tempo esperado: ~45ms (77% mais rápido!)

# 3. Ver chaves criadas
docker exec backend_moday-redis-1 redis-cli KEYS "laravel_cache:product*"
```

### Monitoramento (1 minuto)

```bash
# Ver estatísticas
php artisan cache:analyze --stats

# Ver controllers implementados
php artisan cache:analyze --controllers

# Ver chaves por módulo
php artisan cache:analyze --keys
```

---

## 📋 Checklist de Implementação

### Para Cada Controller:

#### Leituras
- [ ] Importar `use Illuminate\Support\Facades\Cache;`
- [ ] Adicionar `CacheService` no constructor (se usar stats)
- [ ] Implementar `Cache::remember()` no método `index()`
- [ ] Implementar `Cache::remember()` no método `show()`
- [ ] Usar `CacheService` no método `stats()`
- [ ] Definir TTL apropriado (ver tabela abaixo)

#### Escritas
- [ ] Adicionar invalidação no `store()`
- [ ] Adicionar invalidação no `update()`
- [ ] Adicionar invalidação no `delete()`

#### Validação
- [ ] Testar primeira requisição (cache miss)
- [ ] Testar segunda requisição (cache hit)
- [ ] Verificar chaves no Redis
- [ ] Confirmar invalidação funciona
- [ ] Rodar `php artisan cache:analyze`

---

## 📊 Tabela de TTL

| Módulo | TTL (segundos) | TTL (minutos) | Motivo |
|--------|----------------|---------------|--------|
| Produtos | 900 | 15 min | Mudam moderadamente |
| Categorias | 1800 | 30 min | Muito estáveis |
| Pedidos | 600 | 10 min | Mudam frequentemente |
| Clientes | 900 | 15 min | Moderadamente dinâmicos |
| Mesas | 300 | 5 min | Status muda rápido |
| Planos | 7200 | 2 horas | Raramente mudam |
| Permissões | 7200 | 2 horas | Muito estáticas |
| Dashboard | 300 | 5 min | Dados em tempo quase real |
| Estatísticas | 1800 | 30 min | Podem ser antigas |

---

## 🔧 Comandos Úteis

```bash
# Analisar cache
php artisan cache:analyze

# Ver estatísticas
php artisan cache:analyze --stats

# Ver controllers
php artisan cache:analyze --controllers

# Ver chaves
php artisan cache:analyze --keys

# Limpar cache específico
php artisan cache:analyze --clear=product

# Limpar tudo
php artisan cache:clear

# Monitor completo (script bash)
./monitor-cache.sh

# Ver chaves diretamente no Redis
docker exec backend_moday-redis-1 redis-cli KEYS "*product*"

# Ver TTL de uma chave
docker exec backend_moday-redis-1 redis-cli TTL "laravel_cache:products_list_1"

# Monitorar comandos em tempo real
docker exec -it backend_moday-redis-1 redis-cli MONITOR
```

---

## ⚠️ Problemas Comuns

### Redis não conecta

```bash
# Verificar se está rodando
docker ps | grep redis

# Reiniciar
docker-compose restart redis

# Ver logs
docker logs backend_moday-redis-1
```

### Cache não invalida

```bash
# Verificar se método de invalidação está sendo chamado
Log::info('Cache invalidado', ['tenant_id' => $tenantId]);

# Limpar manualmente
php artisan cache:analyze --clear=product
```

### Hit rate baixo (<60%)

1. **Aumentar TTL** - Dados podem ser cacheados por mais tempo
2. **Verificar invalidação** - Talvez esteja invalidando demais
3. **Adicionar mais cache** - Implementar em mais endpoints

---

## 📈 Métricas de Sucesso

### Antes
- ⏱️  Latência: ~250ms
- 🔄 Queries: 15 por request
- 📊 Throughput: 1,000 req/min

### Depois (Meta)
- ⏱️  Latência: ~50ms (-80%)
- 🔄 Queries: 2 por request (-87%)
- 📊 Throughput: 3,500 req/min (+250%)

---

## 📚 Referências Rápidas

- **Guia Completo**: `docs/CACHE_IMPLEMENTATION_GUIDE.md`
- **Exemplos**: `docs/CACHE_EXAMPLES_COMPLETE.md`
- **Resumo**: `docs/CACHE_IMPLEMENTATION_SUMMARY.md`
- **Exemplo Funcional**: `docs/examples/ProductApiController_WithCache.php`

---

## 🎉 Próximos Passos

1. ✅ Implementar cache em 1 controller (ProductApiController)
2. ✅ Testar e validar funcionamento
3. ✅ Monitorar hit rate
4. ✅ Replicar para outros controllers
5. ✅ Acompanhar métricas

**Tempo estimado**: 30 minutos para primeiro controller, depois ~10 min por controller adicional.

---

**Começe agora**: Copie o template acima e aplique no ProductApiController!
