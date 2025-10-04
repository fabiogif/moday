<?php

namespace App\Http\Controllers\Api;

use App\Classes\ApiResponseClass;
use Illuminate\Routing\Controller;
use App\Http\Requests\{Api\TenantFormRequest, StoreUpdateProductRequest, UpdateProductRequest};
use App\Http\Resources\ProductResource;
use App\Services\ProductService;
use Illuminate\Http\{Response, JsonResponse};

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
            \Log::info('ProductApiController::index - Iniciando listagem de produtos');
            
            $data = $this->productService->index();
            
            \Log::info('ProductApiController::index - Dados retornados:', [
                'count' => $data ? count($data) : 0,
                'is_collection' => $data instanceof \Illuminate\Database\Eloquent\Collection,
                'user_id' => auth()->id(),
                'tenant_id' => auth()->user()?->tenant_id
            ]);
            
            if (!$data) {
                return ApiResponseClass::sendResponse([], 'Nenhum produto encontrado', 404);
            }
            
            $resource = ProductResource::collection($data);
            return ApiResponseClass::sendResponse($resource, 'Produtos listados com sucesso', 200);
        } catch (\Exception $ex) {
            \Log::error('ProductApiController::index - Erro:', [
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
            if (!$tenantUuid && auth()->check()) {
                $tenantUuid = auth()->user()->tenant?->uuid;
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
            $user = auth()->user();
            
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
            
            $stats = $this->productService->getStats($user->tenant_id);
            return ApiResponseClass::sendResponse($stats, 'Estatísticas carregadas com sucesso', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao carregar estatísticas');
        }
    }

    public function store(StoreUpdateProductRequest $request):JsonResponse
    {
        try {
            $user = auth()->user();
            
            if (!$user) {
                return ApiResponseClass::unauthorized('Usuário não autenticado');
            }
            
            if (!$user->tenant_id) {
                return ApiResponseClass::forbidden('Usuário não possui tenant associado');
            }

            $data = $request->all();
            $data['tenant_id'] = $user->tenant_id;
            
            if($request->hasFile('image') && $request->image->isValid()){
                $data['image'] = $request->image->store("tenants/{$user->tenant->uuid}/products");
            }
            $product = $this->productService->store($data);

            return ApiResponseClass::sendResponse(new ProductResource($product), 'Produto cadastrado com sucesso', 201);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex);
        }
    }

    public function show($identify):JsonResponse
    {

        $product = $this->productService->getByUuid($identify);
        if(!$product)
        {
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
            $tenant = auth()->user();

            $data = $request->all();
            if ($request->hasFile('image') && $request->image->isValid()) {
                $data['image'] = $request->image->store("tenants/{$tenant->uuid}/products");
            }
            
            $product = $this->productService->update($data, $id);
            if (!$product) {
                return ApiResponseClass::sendResponse('', 'Produto não encontrado', 404);
            }
            
            return ApiResponseClass::sendResponse(new ProductResource($product), 'Produto atualizado com sucesso', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao atualizar produto');
        }
    }

    public function delete($id): JsonResponse
    {
        try {
            $deleted = $this->productService->delete($id);
            if (!$deleted) {
                return ApiResponseClass::sendResponse('', 'Produto não encontrado', 404);
            }
            return ApiResponseClass::sendResponse('', 'Produto removido com sucesso', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao remover produto');
        }
    }
}
