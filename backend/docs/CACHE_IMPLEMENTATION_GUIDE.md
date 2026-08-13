# 🚀 Implementação de Cache nos Módulos Principais

## 📋 Estratégia de Caching

### Princípios
1. **Cache apenas leituras frequentes** - Não cachear writes
2. **TTL apropriado** - Dados estáticos: longo, dados dinâmicos: curto
3. **Invalidação inteligente** - Limpar cache quando dados mudam
4. **Usar CacheService** - Fallback automático se Redis falhar

---

## 🎯 Módulos Prioritários para Cache

### Alta Prioridade (Leitura Frequente)
1. ✅ **Produtos** - Listagens e detalhes
2. ✅ **Categorias** - Usadas em múltiplos lugares
3. ✅ **Pedidos** - Listagens e estatísticas
4. ✅ **Dashboard** - Métricas e gráficos
5. ✅ **Clientes** - Listagens
6. ✅ **Mesas** - Status de ocupação
7. ✅ **Planos** - Raramente mudam
8. ✅ **Permissões** - Muito estáticas

### Média Prioridade
9. ⚠️ **Usuários** - Listagens (TTL curto)
10. ⚠️ **Métodos de Pagamento** - Moderadamente estáticos
11. ⚠️ **Horários de Funcionamento** - Mudam pouco

### Baixa Prioridade (Não Cachear)
- ❌ **Autenticação** - Sempre tempo real
- ❌ **Notificações** - Sempre frescas
- ❌ **Eventos** - Podem mudar frequentemente

---

## 📝 Template de Implementação

### Padrão para Listagens (index)

```php
public function index(Request $request): JsonResponse
{
    try {
        $user = auth()->user();
        $tenantId = $user->tenant_id;
        
        // Gerar chave de cache única
        $cacheKey = "module_list_{$tenantId}_page_{$request->get('page', 1)}_per_page_{$request->get('per_page', 15)}";
        
        // Usar Cache::remember
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
    } catch (\Exception $ex) {
        return ApiResponseClass::rollback($ex, 'Erro ao listar');
    }
}
```

### Padrão para Estatísticas (stats)

```php
public function stats(): JsonResponse
{
    try {
        $user = auth()->user();
        $tenantId = $user->tenant_id;
        
        // Usar CacheService com TTL específico
        $stats = app(CacheService::class)->getModuleStats(
            $tenantId,
            fn() => $this->moduleService->getStats($tenantId)
        );
        
        return ApiResponseClass::sendResponse($stats, 'Estatísticas carregadas', 200);
    } catch (\Exception $ex) {
        return ApiResponseClass::rollback($ex, 'Erro ao carregar estatísticas');
    }
}
```

### Padrão para Detalhes (show)

```php
public function show(string $identify): JsonResponse
{
    try {
        $user = auth()->user();
        $tenantId = $user->tenant_id;
        
        // Cache individual do item
        $cacheKey = "module_item_{$tenantId}_{$identify}";
        
        $item = Cache::remember($cacheKey, 600, function() use ($identify, $tenantId) {
            return $this->moduleService->getByUuid($identify, $tenantId);
        });
        
        if (!$item) {
            return ApiResponseClass::sendResponse('', 'Item não encontrado', 404);
        }
        
        return ApiResponseClass::sendResponse(
            new ModuleResource($item),
            '',
            200
        );
    } catch (\Exception $ex) {
        return ApiResponseClass::rollback($ex, 'Erro ao buscar item');
    }
}
```

### Padrão para Invalidação

```php
public function store(Request $request): JsonResponse
{
    try {
        $user = auth()->user();
        $tenantId = $user->tenant_id;
        
        // Criar o registro
        $item = $this->moduleService->store($data);
        
        // Invalidar caches relacionados
        $this->invalidateModuleCache($tenantId);
        
        return ApiResponseClass::sendResponse(
            new ModuleResource($item),
            'Criado com sucesso',
            201
        );
    } catch (\Exception $ex) {
        return ApiResponseClass::rollback($ex);
    }
}

private function invalidateModuleCache(int $tenantId): void
{
    // Limpar cache de listagens
    Cache::tags(['module', "tenant_{$tenantId}"])->flush();
    
    // OU usar CacheService
    app(CacheService::class)->invalidateModuleCache($tenantId);
}
```

---

## 🔧 Implementações Práticas

### 1. ProductApiController com Cache

```php
<?php

namespace App\Http\Controllers\Api;

use App\Classes\ApiResponseClass;
use Illuminate\Routing\Controller;
use App\Http\Resources\ProductResource;
use App\Services\{ProductService, CacheService};
use Illuminate\Http\{JsonResponse, Request};
use Illuminate\Support\Facades\Cache;

class ProductApiController extends Controller
{
    public function __construct(
        private readonly ProductService $productService,
        private readonly CacheService $cacheService
    ) {}

    /**
     * Listar produtos (COM CACHE)
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();
            $tenantId = $user->tenant_id;
            
            // Gerar chave incluindo filtros e paginação
            $page = $request->get('page', 1);
            $perPage = $request->get('per_page', 15);
            $filter = $request->filter ?? '';
            
            $cacheKey = "products_list_{$tenantId}_page_{$page}_per_{$perPage}_filter_" . md5($filter);
            
            // Cache de 15 minutos para listagem de produtos
            $products = Cache::remember($cacheKey, 900, function() use ($tenantId, $page, $perPage, $filter) {
                return $this->productService->paginate(
                    page: $page,
                    totalPerPage: $perPage,
                    filter: $filter,
                    tenantId: $tenantId
                );
            });
            
            return ApiResponseClass::sendResponsePaginate(
                ProductResource::class,
                $products,
                200
            );
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao listar produtos');
        }
    }

    /**
     * Estatísticas de produtos (COM CACHE)
     */
    public function stats(): JsonResponse
    {
        try {
            $user = auth()->user();
            $tenantId = $user->tenant_id;
            
            // Usar CacheService - cache de 30 minutos
            $stats = $this->cacheService->getProductStats(
                $tenantId,
                fn() => $this->productService->getStats($tenantId)
            );
            
            return ApiResponseClass::sendResponse($stats, 'Estatísticas carregadas', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao carregar estatísticas');
        }
    }

    /**
     * Detalhes do produto (COM CACHE)
     */
    public function show(string $identify): JsonResponse
    {
        try {
            $user = auth()->user();
            $tenantId = $user->tenant_id;
            
            // Cache de 10 minutos para detalhes
            $cacheKey = "product_detail_{$tenantId}_{$identify}";
            
            $product = Cache::remember($cacheKey, 600, function() use ($identify, $tenantId) {
                return $this->productService->getByUuid($identify, $tenantId);
            });
            
            if (!$product) {
                return ApiResponseClass::sendResponse('', 'Produto não encontrado', 404);
            }
            
            return ApiResponseClass::sendResponse(
                new ProductResource($product),
                '',
                200
            );
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao buscar produto');
        }
    }

    /**
     * Criar produto (INVALIDAR CACHE)
     */
    public function store(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();
            $tenantId = $user->tenant_id;
            
            $product = $this->productService->store($data);
            
            // Invalidar caches de produtos
            $this->invalidateProductCache($tenantId);
            
            return ApiResponseClass::sendResponse(
                new ProductResource($product),
                'Produto cadastrado com sucesso',
                201
            );
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex);
        }
    }

    /**
     * Atualizar produto (INVALIDAR CACHE)
     */
    public function update(Request $request, string $id): JsonResponse
    {
        try {
            $user = auth()->user();
            $tenantId = $user->tenant_id;
            
            $product = $this->productService->update($request->all(), $id, $tenantId);
            
            // Invalidar caches
            $this->invalidateProductCache($tenantId);
            
            return ApiResponseClass::sendResponse(
                new ProductResource($product),
                'Produto atualizado com sucesso',
                200
            );
        } catch (\Exception $ex) {
            return ApiResponseClass->rollback($ex);
        }
    }

    /**
     * Deletar produto (INVALIDAR CACHE)
     */
    public function delete(string $identify): JsonResponse
    {
        try {
            $user = auth()->user();
            $tenantId = $user->tenant_id;
            
            $deleted = $this->productService->delete($identify, $tenantId);
            
            // Invalidar caches
            $this->invalidateProductCache($tenantId);
            
            return ApiResponseClass::sendResponse('', 'Produto deletado com sucesso', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex);
        }
    }

    /**
     * Invalidar cache de produtos
     */
    private function invalidateProductCache(int $tenantId): void
    {
        // Método 1: Usar CacheService
        $this->cacheService->invalidateProductCache($tenantId);
        
        // Método 2: Limpar manualmente (se necessário)
        Cache::forget("products_stats_{$tenantId}");
        
        // Limpar todas as páginas de listagem (padrão simplificado)
        for ($page = 1; $page <= 10; $page++) {
            Cache::forget("products_list_{$tenantId}_page_{$page}_per_15_filter_");
        }
    }
}
```

### 2. CategoryApiController com Cache

```php
<?php

namespace App\Http\Controllers\Api;

use Illuminate\Support\Facades\Cache;

class CategoryApiController extends Controller
{
    public function __construct(
        private readonly CategoryService $categoryService,
        private readonly CacheService $cacheService
    ) {}

    public function index(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();
            $tenantId = $user->tenant_id;
            
            // Categorias mudam pouco - cache de 30 minutos
            $cacheKey = "categories_list_{$tenantId}";
            
            $categories = Cache::remember($cacheKey, 1800, function() use ($tenantId, $request) {
                return $this->categoryService->paginate(
                    page: $request->get('page', 1),
                    totalPerPage: $request->get('per_page', 10),
                    filter: $request->filter ?? '',
                    tenantId: $tenantId
                );
            });
            
            return ApiResponseClass::sendResponsePaginate(
                CategoryResource::class,
                $categories,
                200
            );
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex);
        }
    }

    public function stats(): JsonResponse
    {
        try {
            $user = auth()->user();
            $tenantId = $user->tenant_id;
            
            // Cache de 1 hora para estatísticas de categorias
            $stats = $this->cacheService->getCategoryStats(
                $tenantId,
                fn() => $this->categoryService->getStats($tenantId)
            );
            
            return ApiResponseClass::sendResponse($stats, 'Estatísticas carregadas', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex);
        }
    }
}
```

### 3. OrderApiController com Cache

```php
<?php

namespace App\Http\Controllers\Api;

class OrderApiController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        try {
            $user = auth()->user();
            $tenantId = $user->tenant_id;
            
            // Pedidos mudam frequentemente - cache de apenas 10 minutos
            $page = $request->get('page', 1);
            $perPage = $request->get('per_page', 15);
            $status = $request->get('status');
            
            $cacheKey = "orders_list_{$tenantId}_page_{$page}_status_" . ($status ?? 'all');
            
            $orders = Cache::remember($cacheKey, 600, function() use ($tenantId, $page, $perPage, $status) {
                return $this->orderService->paginate([
                    'page' => $page,
                    'per_page' => $perPage,
                    'status' => $status,
                    'tenant_id' => $tenantId
                ]);
            });
            
            return ApiResponseClass::sendResponsePaginate(
                OrderResource::class,
                $orders,
                200
            );
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex);
        }
    }
}
```

### 4. DashboardMetricsController com Cache

```php
<?php

namespace App\Http\Controllers\Api;

class DashboardMetricsController extends Controller
{
    public function getMetricsOverview(): JsonResponse
    {
        try {
            $user = auth()->user();
            $tenantId = $user->tenant_id;
            
            // Dashboard muda constantemente - cache curto de 5 minutos
            $metrics = $this->cacheService->getDashboardMetrics(
                $tenantId,
                fn() => [
                    'revenue' => $this->calculateRevenue($tenantId),
                    'orders_count' => $this->countOrders($tenantId),
                    'clients_count' => $this->countClients($tenantId),
                    'products_count' => $this->countProducts($tenantId),
                ]
            );
            
            return ApiResponseClass::sendResponse($metrics, 'Métricas carregadas', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex);
        }
    }

    public function getTopProducts(): JsonResponse
    {
        try {
            $user = auth()->user();
            $tenantId = $user->tenant_id;
            
            // Top products - cache de 10 minutos
            $topProducts = $this->cacheService->getTopProducts(
                $tenantId,
                fn() => $this->orderService->getTopSellingProducts($tenantId, 10)
            );
            
            return ApiResponseClass::sendResponse($topProducts, 'Top produtos carregados', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex);
        }
    }
}
```

### 5. PlanApiController com Cache

```php
<?php

namespace App\Http\Controllers\Api;

class PlanApiController extends Controller
{
    public function index(): JsonResponse
    {
        try {
            // Planos raramente mudam - cache de 2 horas
            $plans = Cache::remember('plans_list_all', 7200, function() {
                return $this->planService->index();
            });
            
            return ApiResponseClass::sendResponse(
                PlanResource::collection($plans),
                'Planos listados',
                200
            );
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex);
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            $cacheKey = "plan_detail_{$id}";
            
            $plan = Cache::remember($cacheKey, 7200, function() use ($id) {
                return $this->planService->show($id);
            });
            
            if (!$plan) {
                return ApiResponseClass::sendResponse('', 'Plano não encontrado', 404);
            }
            
            return ApiResponseClass::sendResponse(
                new PlanResource($plan),
                '',
                200
            );
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex);
        }
    }
}
```

---

## 🎯 Resumo de TTL por Módulo

| Módulo | TTL | Motivo |
|--------|-----|--------|
| Produtos | 15 min | Mudam moderadamente |
| Categorias | 30 min | Muito estáveis |
| Pedidos | 10 min | Mudam frequentemente |
| Clientes | 15 min | Moderadamente dinâmicos |
| Mesas | 5 min | Status muda rápido |
| Planos | 2 horas | Raramente mudam |
| Permissões | 2 horas | Muito estáticas |
| Dashboard | 5 min | Dados em tempo quase real |
| Estatísticas | 30 min | Podem ser um pouco antigas |

---

## ✅ Checklist de Implementação

- [ ] Identificar endpoints de leitura
- [ ] Adicionar CacheService ao constructor
- [ ] Implementar Cache::remember nas listagens
- [ ] Implementar invalidação nos creates/updates/deletes
- [ ] Testar performance antes/depois
- [ ] Monitorar hit rate do cache
- [ ] Documentar chaves de cache usadas

---

**Próximo passo**: Aplicar este padrão nos controllers principais
