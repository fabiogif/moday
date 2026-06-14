<?php

namespace App\Http\Controllers\Api;

use App\Classes\ApiResponseClass;
use App\Helpers\ImageHelper;
use Illuminate\Routing\Controller;
use App\Http\Requests\{Api\TenantFormRequest, StoreUpdateProductRequest, UpdateProductRequest};
use App\Http\Resources\ProductResource;
use App\Services\ProductService;
use Illuminate\Http\{Response, JsonResponse};
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

/**
 * @OA\Tag(
 *     name="Produtos",
 *     description="Gerenciamento de produtos"
 * )
 */
class ProductApiController extends Controller
{

    public function __construct(private readonly ProductService $productService)
    {
      
    }

    /**
     * @OA\Get(
     *     path="/api/product",
     *     summary="Listar produtos",
     *     description="Retorna lista de produtos do tenant autenticado",
     *     tags={"Produtos"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Response(
     *         response=200,
     *         description="Lista de produtos",
     *         @OA\JsonContent(
     *             @OA\Property(property="data", type="array", @OA\Items(
     *                 @OA\Property(property="id", type="integer", example=1),
     *                 @OA\Property(property="name", type="string", example="Pizza Margherita"),
     *                 @OA\Property(property="price", type="number", format="float", example=25.90),
     *                 @OA\Property(property="description", type="string", example="Pizza com molho de tomate, mussarela e manjericão"),
     *                 @OA\Property(property="image", type="string", example="products/pizza-margherita.jpg")
     *             ))
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Não autenticado"
     *     )
     * )
     */
    public function index(): JsonResponse
    {
        try {
            Log::info('ProductApiController::index - Iniciando listagem de produtos');
            
            $data = $this->productService->index();
            
            Log::info('ProductApiController::index - Dados retornados:', [
                'count' => $data ? count($data) : 0,
                'is_collection' => $data instanceof \Illuminate\Database\Eloquent\Collection,
                'user_id' => Auth::id(),
                'tenant_id' => Auth::user()?->tenant_id
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

    public function productsByTenant(TenantFormRequest $request): JsonResponse
    {
        try {
            // Permitir duas formas de obtenção do tenant:
            // 1) via token_company (UUID) vindo do request
            // 2) via usuário autenticado (tenant vinculado)
            $tenantUuid = $request->input('token_company');
            if (!$tenantUuid && Auth::check()) {
                $tenantUuid = Auth::user()->tenant?->uuid;
            }

            if (!$tenantUuid) {
                return ApiResponseClass::sendResponse('', 'Token da empresa é obrigatório', 400);
            }

            $products = $this->productService->getProductsByTenantUuid($tenantUuid, $request->get('categories', []));
            if (!$products) {
                return ApiResponseClass::sendResponse([], 'Nenhum produto encontrado para esta empresa', 404);
            }

            return ApiResponseClass::sendResponse(ProductResource::collection($products), 'Produtos listados com sucesso', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao listar produtos da empresa');
        }
    }

    public function productsByAuthenticatedUser(): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return ApiResponseClass::unauthorized('Usuário não autenticado');
            }
            
            if (!$user->tenant_id) {
                return ApiResponseClass::forbidden('Usuário não possui tenant associado');
            }
            
            $products = $this->productService->getProductsByTenantId($user->tenant_id);
            if (!$products) {
                return ApiResponseClass::sendResponse([], 'Nenhum produto encontrado', 404);
            }
    
            return ApiResponseClass::sendResponse(ProductResource::collection($products), 'Produtos listados com sucesso', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao listar produtos');
        }
    }

    /**
     * Produtos disponíveis para venda (PDV, novo pedido) — ativos e com estoque.
     */
    public function catalogProductsByAuthenticatedUser(): JsonResponse
    {
        try {
            $user = Auth::user();

            if (!$user) {
                return ApiResponseClass::unauthorized('Usuário não autenticado');
            }

            if (!$user->tenant_id) {
                return ApiResponseClass::forbidden('Usuário não possui tenant associado');
            }

            $products = $this->productService->getCatalogProductsByTenantId($user->tenant_id);

            return ApiResponseClass::sendResponse(
                ProductResource::collection($products),
                'Produtos do catálogo de venda listados com sucesso',
                200
            );
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao listar produtos do catálogo');
        }
    }

    public function stats(): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return ApiResponseClass::unauthorized('Usuário não autenticado');
            }
            
            if (!$user->tenant_id) {
                return ApiResponseClass::forbidden('Usuário não possui tenant associado');
            }
            
            $stats = $this->productService->getStats($user->tenant_id);
            return ApiResponseClass::sendResponse($stats, 'Estatísticas carregadas com sucesso', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao carregar estatísticas');
        }
    }

    public function store(StoreUpdateProductRequest $request):JsonResponse
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return ApiResponseClass::unauthorized('Usuário não autenticado');
            }
            
            if (!$user->tenant_id) {
                return ApiResponseClass::forbidden('Usuário não possui tenant associado');
            }

            $data = $request->except(['_method', 'trial_info']);
            $data['tenant_id'] = $user->tenant_id;
            
            if($request->hasFile('image') && $request->image->isValid()){
                $fileUploadService = app(\App\Services\FileUploadService::class);
                $uploadResult = $fileUploadService->uploadFile(
                    $request->image, 
                    'product', 
                    $user->tenant->uuid
                );
                $data['image'] = $uploadResult['path'];
            }
            $product = $this->productService->store($data);

            if (isset($data['qtd_stock']) && (int) $data['qtd_stock'] > 0) {
                app(\App\Services\Stock\StockService::class)->ensureStockFromDeclaredQuantity(
                    $user->tenant_id,
                    $product->id,
                    $user->id
                );
                $product->refresh();
            }

            return ApiResponseClass::sendResponse(new ProductResource($product), 'Produto criado com sucesso', 201);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex);
        }
    }

    public function show($identify):JsonResponse
    {
        $user = Auth::user();
        
        if (!$user) {
            return ApiResponseClass::unauthorized('Usuário não autenticado');
        }
        
        if (!$user->tenant_id) {
            return ApiResponseClass::forbidden('Usuário não possui tenant associado');
        }

        // Buscar por UUID ou ID numérico
        $product = $this->productService->getByIdentifier($identify);
        if(!$product)
        {
            return ApiResponseClass::sendResponse('', 'Produto não encontrado', 404);
        }
        
        // Verificar se o produto pertence ao tenant do usuário
        // Retornar 404 ao invés de 403 para não revelar a existência do produto
        if ($product->tenant_id !== $user->tenant_id) {
            return ApiResponseClass::sendResponse('', 'Produto não encontrado', 404);
        }

        return ApiResponseClass::sendResponse(new ProductResource($product), '', 200);
    }

    /**
     * @OA\Get(
     *     path="/api/product/{uuid}/similar",
     *     summary="Listar produtos similares",
     *     description="Retorna produtos similares baseados nas categorias",
     *     tags={"Produtos"},
     *     security={{"bearerAuth": {}}},
     *     @OA\Parameter(
     *         name="uuid",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Lista de produtos similares"
     *     )
     * )
     */
    public function similarProducts($uuid): JsonResponse
    {
        try {
            $product = $this->productService->getByUuid($uuid);
            if (!$product) {
                return ApiResponseClass::sendResponse('', 'Produto não encontrado', 404);
            }

            $similarProducts = $product->similarProducts();
            return ApiResponseClass::sendResponse(
                ProductResource::collection($similarProducts), 
                'Produtos similares listados com sucesso', 
                200
            );
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao listar produtos similares');
        }
    }

    public function update(StoreUpdateProductRequest $request, $id): JsonResponse
    {
        try {
            $user = Auth::user();
         
            if (!$user) {
                return ApiResponseClass::unauthorized('Usuário não autenticado');
            }
            
            if (!$user->tenant_id) {
                return ApiResponseClass::forbidden('Usuário não possui tenant associado');
            }
            
            // Verificar se o produto existe e pertence ao tenant
            $existingProduct = $this->productService->getByIdentifier($id);
            
            if (!$existingProduct) {
                return ApiResponseClass::sendResponse('', 'Produto não encontrado', 404);
            }
            
            // Retornar 404 ao invés de 403 para não revelar a existência do produto
            if ($existingProduct->tenant_id !== $user->tenant_id) {
                return ApiResponseClass::sendResponse('', 'Produto não encontrado', 404);
            }

      

            $data = $request->except(['_method', 'trial_info']);
            if ($request->hasFile('image') && $request->image->isValid()) {
                $fileUploadService = app(\App\Services\FileUploadService::class);
                $uploadResult = $fileUploadService->uploadFile(
                    $request->image, 
                    'product', 
                    $user->tenant->uuid
                );
                $data['image'] = $uploadResult['path'];
            }
            
            $product = $this->productService->update($data, $existingProduct->id);

            if (isset($data['qtd_stock']) && (int) $data['qtd_stock'] > 0) {
                app(\App\Services\Stock\StockService::class)->ensureStockFromDeclaredQuantity(
                    $user->tenant_id,
                    $existingProduct->id,
                    $user->id
                );
                $product->refresh();
            }
            
            return ApiResponseClass::sendResponse(new ProductResource($product), 'Produto atualizado com sucesso', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao atualizar produto');
        }
    }

    public function delete($id): JsonResponse
    {
        try {
            $user = Auth::user();
            
            if (!$user) {
                return ApiResponseClass::unauthorized('Usuário não autenticado');
            }
            
            if (!$user->tenant_id) {
                return ApiResponseClass::forbidden('Usuário não possui tenant associado');
            }
            
            // Verificar se o produto existe e pertence ao tenant
            $existingProduct = $this->productService->getByIdentifier($id);
            
            if (!$existingProduct) {
                return ApiResponseClass::sendResponse('', 'Produto não encontrado', 404);
            }
            
            // Retornar 404 ao invés de 403 para não revelar a existência do produto
            if ($existingProduct->tenant_id !== $user->tenant_id) {
                return ApiResponseClass::sendResponse('', 'Produto não encontrado', 404);
            }
            
            $guard = app(\App\Services\DeletionGuardService::class);
            $check = $guard->verificarVinculoPedidoAtivo($existingProduct->id, 'produto', $user->tenant_id);
            if ($check['blocked']) {
                return ApiResponseClass::conflict(
                    'Produto não pode ser excluído, existe um pedido ativo ou não arquivado vinculado.',
                    ['orders' => $check['orders']]
                );
            }

            $deleted = $this->productService->delete($existingProduct->id);
            
            if ($deleted) {
                return response()->json(null, 204);
            }

            return ApiResponseClass::throw(new \RuntimeException('Falha ao deletar produto'), 'Erro ao remover produto');
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao remover produto');
        }
    }
}
