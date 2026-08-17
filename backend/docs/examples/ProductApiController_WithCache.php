<?php

namespace App\Http\Controllers\Api;

use App\Classes\ApiResponseClass;
use App\Helpers\ImageHelper;
use Illuminate\Routing\Controller;
use App\Http\Requests\{Api\TenantFormRequest, StoreUpdateProductRequest, UpdateProductRequest};
use App\Http\Resources\ProductResource;
use App\Services\{ProductService, CacheService};
use Illuminate\Http\{Response, JsonResponse};
use Illuminate\Support\Facades\{Log, Cache};

/**
 * Product API Controller com Cache Implementado
 * 
 * Melhorias implementadas:
 * - Cache em listagens (15 minutos)
 * - Cache em estatísticas (30 minutos)
 * - Cache em detalhes de produto (10 minutos)
 * - Invalidação automática ao criar/atualizar/deletar
 */
class ProductApiController extends Controller
{
    public function __construct(
        private readonly ProductService $productService,
        private readonly CacheService $cacheService
    ) {}

    /**
     * Listar produtos (COM CACHE - 15 minutos)
     */
    public function index(): JsonResponse
    {
        try {
            Log::info('ProductApiController::index - Iniciando listagem de produtos');
            
            $user = auth()->user();
            if (!$user || !$user->tenant_id) {
                return ApiResponseClass::unauthorized('Usuário não autenticado');
            }
            
            // Gerar chave de cache única para este tenant
            $cacheKey = "products_list_{$user->tenant_id}";
            
            // Cache de 15 minutos (900 segundos)
            $data = Cache::remember($cacheKey, 900, function() {
                return $this->productService->index();
            });
            
            Log::info('ProductApiController::index - Dados retornados:', [
                'count' => $data ? count($data) : 0,
                'from_cache' => Cache::has("products_list_{$user->tenant_id}"),
                'tenant_id' => $user->tenant_id
            ]);
            
            if (!$data) {
                return ApiResponseClass::sendResponse([], 'Nenhum produto encontrado', 404);
            }
            
            $resource = ProductResource::collection($data);
            return ApiResponseClass::sendResponse($resource, 'Produtos listados com sucesso', 200);
        } catch (\Exception $ex) {
            Log::error('ProductApiController::index - Erro:', [
                'message' => $ex->getMessage(),
                'trace' => $ex->getTraceAsString()
            ]);
            return ApiResponseClass::rollback($ex, 'Erro ao listar produtos');
        }
    }

    /**
     * Produtos por tenant (COM CACHE - 15 minutos)
     */
    public function productsByTenant(TenantFormRequest $request): JsonResponse
    {
        try {
            $tenantUuid = $request->input('token_company');
            if (!$tenantUuid && auth()->check()) {
                $tenantUuid = auth()->user()->tenant?->uuid;
            }

            if (!$tenantUuid) {
                return ApiResponseClass::sendResponse('', 'Token da empresa é obrigatório', 400);
            }

            // Cache com chave baseada no UUID do tenant e categorias
            $categoriesKey = md5(json_encode($request->get('categories', [])));
            $cacheKey = "products_by_tenant_{$tenantUuid}_categories_{$categoriesKey}";
            
            $products = Cache::remember($cacheKey, 900, function() use ($tenantUuid, $request) {
                return $this->productService->getProductsByTenantUuid(
                    $tenantUuid, 
                    $request->get('categories', [])
                );
            });
            
            if (!$products) {
                return ApiResponseClass::sendResponse([], 'Nenhum produto encontrado para esta empresa', 404);
            }

            return ApiResponseClass::sendResponse(
                ProductResource::collection($products), 
                'Produtos listados com sucesso', 
                200
            );
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao listar produtos da empresa');
        }
    }

    /**
     * Produtos do usuário autenticado (COM CACHE - 15 minutos)
     */
    public function productsByAuthenticatedUser(): JsonResponse
    {
        try {
            $user = auth()->user();
            
            if (!$user) {
                return ApiResponseClass::unauthorized('Usuário não autenticado');
            }
            
            if (!$user->tenant_id) {
                return ApiResponseClass::forbidden('Usuário não possui tenant associado');
            }
            
            // Cache específico para listagem do usuário
            $cacheKey = "products_user_{$user->id}_tenant_{$user->tenant_id}";
            
            $products = Cache::remember($cacheKey, 900, function() use ($user) {
                return $this->productService->getProductsByTenantId($user->tenant_id);
            });
            
            if (!$products) {
                return ApiResponseClass::sendResponse([], 'Nenhum produto encontrado', 404);
            }
    
            return ApiResponseClass::sendResponse(
                ProductResource::collection($products), 
                'Produtos listados com sucesso', 
                200
            );
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao listar produtos');
        }
    }

    /**
     * Estatísticas de produtos (COM CACHE - 30 minutos)
     */
    public function stats(): JsonResponse
    {
        try {
            $user = auth()->user();
            
            if (!$user) {
                return ApiResponseClass::unauthorized('Usuário não autenticado');
            }
            
            if (!$user->tenant_id) {
                return ApiResponseClass::forbidden('Usuário não possui tenant associado');
            }
            
            // Usar CacheService com TTL de 30 minutos
            $stats = $this->cacheService->getProductStats(
                $user->tenant_id,
                fn() => $this->productService->getStats($user->tenant_id)
            );
            
            Log::info('ProductApiController::stats - Estatísticas carregadas', [
                'tenant_id' => $user->tenant_id,
                'from_cache' => Cache::has("product_stats_{$user->tenant_id}")
            ]);
            
            return ApiResponseClass::sendResponse($stats, 'Estatísticas carregadas com sucesso', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao carregar estatísticas');
        }
    }

    /**
     * Criar produto (INVALIDAR CACHE)
     */
    public function store(StoreUpdateProductRequest $request): JsonResponse
    {
        try {
            $user = auth()->user();
            
            if (!$user) {
                return ApiResponseClass::unauthorized('Usuário não autenticado');
            }
            
            if (!$user->tenant_id) {
                return ApiResponseClass::forbidden('Usuário não possui tenant associado');
            }

            $data = $request->except(['_method']);
            $data['tenant_id'] = $user->tenant_id;
            
            if($request->hasFile('image') && $request->image->isValid()){
                $fileUploadService = app(\App\Services\FileUploadService::class);
                $uploadResult = $fileUploadService->uploadFile(
                    $request->image, 
                    'product', 
                    $user->tenant->uuid
                );
                $data['image'] = $uploadResult['url'];
            }
            
            $product = $this->productService->store($data);

            // INVALIDAR CACHE após criar
            $this->invalidateProductCache($user->tenant_id);
            
            Log::info('ProductApiController::store - Produto criado e cache invalidado', [
                'product_id' => $product->id,
                'tenant_id' => $user->tenant_id
            ]);

            return ApiResponseClass::sendResponse(
                new ProductResource($product), 
                'Produto criado com sucesso', 
                201
            );
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex);
        }
    }

    /**
     * Detalhes do produto (COM CACHE - 10 minutos)
     */
    public function show($identify): JsonResponse
    {
        $user = auth()->user();
        
        if (!$user) {
            return ApiResponseClass::unauthorized('Usuário não autenticado');
        }
        
        if (!$user->tenant_id) {
            return ApiResponseClass::forbidden('Usuário não possui tenant associado');
        }

        // Cache de 10 minutos para detalhes do produto
        $cacheKey = "product_detail_{$user->tenant_id}_{$identify}";
        
        $product = Cache::remember($cacheKey, 600, function() use ($identify) {
            return $this->productService->getByIdentifier($identify);
        });
        
        if(!$product) {
            return ApiResponseClass::sendResponse('', 'Produto não encontrado', 404);
        }
        
        // Verificar se o produto pertence ao tenant do usuário
        if ($product->tenant_id !== $user->tenant_id) {
            return ApiResponseClass::sendResponse('', 'Produto não encontrado', 404);
        }

        return ApiResponseClass::sendResponse(new ProductResource($product), '', 200);
    }

    /**
     * Produtos similares (COM CACHE - 15 minutos)
     */
    public function similarProducts($uuid): JsonResponse
    {
        try {
            // Cache para produtos similares
            $cacheKey = "product_similar_{$uuid}";
            
            $similarProducts = Cache::remember($cacheKey, 900, function() use ($uuid) {
                $product = $this->productService->getByUuid($uuid);
                if (!$product) {
                    return null;
                }
                return $product->similarProducts();
            });
            
            if ($similarProducts === null) {
                return ApiResponseClass::sendResponse('', 'Produto não encontrado', 404);
            }

            return ApiResponseClass::sendResponse(
                ProductResource::collection($similarProducts), 
                'Produtos similares listados com sucesso', 
                200
            );
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao listar produtos similares');
        }
    }

    /**
     * Atualizar produto (INVALIDAR CACHE)
     */
    public function update(StoreUpdateProductRequest $request, $id): JsonResponse
    {
        try {
            $user = auth()->user();
         
            if (!$user) {
                return ApiResponseClass::unauthorized('Usuário não autenticado');
            }
            
            if (!$user->tenant_id) {
                return ApiResponseClass::forbidden('Usuário não possui tenant associado');
            }
            
            $existingProduct = $this->productService->getByIdentifier($id);
            
            if (!$existingProduct) {
                return ApiResponseClass::sendResponse('', 'Produto não encontrado', 404);
            }
            
            if ($existingProduct->tenant_id !== $user->tenant_id) {
                return ApiResponseClass::sendResponse('', 'Produto não encontrado', 404);
            }

            $data = $request->except(['_method']);
            if ($request->hasFile('image') && $request->image->isValid()) {
                $fileUploadService = app(\App\Services\FileUploadService::class);
                $uploadResult = $fileUploadService->uploadFile(
                    $request->image, 
                    'product', 
                    $user->tenant->uuid
                );
                $data['image'] = $uploadResult['url'];
            }
            
            $product = $this->productService->update($data, $existingProduct->id);
            
            // INVALIDAR CACHE após atualizar
            $this->invalidateProductCache($user->tenant_id);
            
            Log::info('ProductApiController::update - Produto atualizado e cache invalidado', [
                'product_id' => $product->id,
                'tenant_id' => $user->tenant_id
            ]);
            
            return ApiResponseClass::sendResponse(
                new ProductResource($product), 
                'Produto atualizado com sucesso', 
                200
            );
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao atualizar produto');
        }
    }

    /**
     * Deletar produto (INVALIDAR CACHE)
     */
    public function delete($id): JsonResponse
    {
        try {
            $user = auth()->user();
            
            if (!$user) {
                return ApiResponseClass::unauthorized('Usuário não autenticado');
            }
            
            if (!$user->tenant_id) {
                return ApiResponseClass::forbidden('Usuário não possui tenant associado');
            }
            
            $existingProduct = $this->productService->getByIdentifier($id);
            
            if (!$existingProduct) {
                return ApiResponseClass::sendResponse('', 'Produto não encontrado', 404);
            }
            
            if ($existingProduct->tenant_id !== $user->tenant_id) {
                return ApiResponseClass::sendResponse('', 'Produto não encontrado', 404);
            }
            
            // Verificar pedidos em preparo
            $guard = app(\App\Services\DeletionGuardService::class);
            $check = $guard->checkProductHasPreparingOrders($existingProduct->id);
            if ($check['blocked']) {
                $first = $check['orders'][0] ?? null;
                $message = $first
                    ? "Não é possível excluir: produto presente no pedido #{$first} com status Em Preparo"
                    : 'Não é possível excluir: produto presente em pedidos com status Em Preparo';
                return ApiResponseClass::validationError([
                    'orders_in_preparing' => $check['orders']
                ], $message);
            }

            $deleted = $this->productService->delete($existingProduct->id);
            
            // INVALIDAR CACHE após deletar
            $this->invalidateProductCache($user->tenant_id);
            
            Log::info('ProductApiController::delete - Produto deletado e cache invalidado', [
                'product_id' => $existingProduct->id,
                'tenant_id' => $user->tenant_id
            ]);
            
            return ApiResponseClass::sendResponse('', 'Produto deletado com sucesso', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao remover produto');
        }
    }

    /**
     * Invalidar todos os caches relacionados a produtos
     * 
     * @param int $tenantId
     * @return void
     */
    private function invalidateProductCache(int $tenantId): void
    {
        // Usar CacheService para invalidação centralizada
        $this->cacheService->invalidateProductCache($tenantId);
        
        Log::info('ProductApiController - Cache de produtos invalidado', [
            'tenant_id' => $tenantId,
            'caches_cleared' => [
                "products_list_{$tenantId}",
                "product_stats_{$tenantId}",
                "product_detail_*",
                "products_user_*"
            ]
        ]);
    }
}
