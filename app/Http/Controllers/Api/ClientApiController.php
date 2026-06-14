<?php

namespace App\Http\Controllers\Api;

use App\Classes\ApiResponseClass;
use App\Http\Controllers\Api\Controller;
use App\Http\Requests\StoreClient;
use App\Http\Requests\UpdateClient;
use App\Http\Requests\Api\CheckCpfRequest;
use App\Http\Resources\ClientResource;
use App\Services\ClientService;
use App\Services\AuthTenantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ClientApiController extends Controller
{
    public function __construct(protected ClientService $clientService, protected AuthTenantService $authTenantService)
    {
    }

    /**
     * @OA\Get(
     *     path="/api/client",
     *     summary="Listar clientes",
     *     description="Retorna lista de todos os clientes",
     *     tags={"Cliente"},
     *     @OA\Response(
     *         response=200,
     *         description="Lista de clientes retornada com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="array", @OA\Items(ref="#/components/schemas/ClientResource")),
     *             @OA\Property(property="message", type="string", example="Clientes listados com sucesso")
     *         )
     *     )
     * )
     */
    public function index(Request $request): JsonResponse
    {
        try {
            [$_user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();
            $clients = $this->clientService->getClientsByTenant($tenantId);
            return ApiResponseClass::sendResponse(ClientResource::collection($clients), 'Clientes listados com sucesso', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao listar clientes');
        }
    }

    /**
     * @OA\Post(
     *     path="/api/client",
     *     summary="Criar novo cliente",
     *     description="Cria um novo cliente no sistema",
     *     tags={"Cliente"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/StoreClient")
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Cliente criado com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/ClientResource"),
     *             @OA\Property(property="message", type="string", example="Cliente cadastrado com sucesso")
     *         )
     *     )
     * )
     */
    public function store(StoreClient $request): JsonResponse
    {
        try {
            [$_user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();
            $data = $request->all();
            $data['tenant_id'] = $tenantId;

            // For B2B clients: derive name from company_name if name is absent
            if (empty($data['name']) && !empty($data['company_name'])) {
                $data['name'] = $data['company_name'];
            }

            $client = $this->clientService->createClient($data);
            return ApiResponseClass::sendResponse(new ClientResource($client), 'Cliente cadastrado com sucesso', 201);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex);
        }
    }

    /**
     * @OA\Get(
     *     path="/api/client/{id}",
     *     summary="Buscar cliente por ID",
     *     description="Retorna um cliente específico pelo ID",
     *     tags={"Cliente"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID do cliente",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Cliente encontrado com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/ClientResource"),
     *             @OA\Property(property="message", type="string", example="Cliente encontrado com sucesso")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Cliente não encontrado"
     *     )
     * )
     */
    public function show($id): JsonResponse
    {
        try {
            [$_user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();
            $client = $this->clientService->getClientById($id);
            
            if (!$client) {
                return ApiResponseClass::sendResponse(null, 'Cliente não encontrado', 404);
            }
            
            if ($client->tenant_id !== $tenantId) {
                return ApiResponseClass::forbidden('Acesso negado ao cliente');
            }
            
            return ApiResponseClass::sendResponse(new ClientResource($client), 'Cliente encontrado com sucesso', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex);
        }
    }

    /**
     * @OA\Put(
     *     path="/api/client/{id}",
     *     summary="Atualizar cliente",
     *     description="Atualiza um cliente existente",
     *     tags={"Cliente"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID do cliente",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/UpdateClient")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Cliente atualizado com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", ref="#/components/schemas/ClientResource"),
     *             @OA\Property(property="message", type="string", example="Cliente atualizado com sucesso")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Cliente não encontrado"
     *     )
     * )
     */
    public function update(UpdateClient $request, $id): JsonResponse
    {
        try {
            [$_user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();
            // Verificar se o cliente existe e pertence ao tenant do usuário
            $existingClient = $this->clientService->getClientById($id);
            
            if (!$existingClient) {
                return ApiResponseClass::sendResponse(null, 'Cliente não encontrado', 404);
            }
            
            if ($existingClient->tenant_id !== $tenantId) {
                return ApiResponseClass::forbidden('Acesso negado ao cliente');
            }
            
            $client = $this->clientService->updateClient($id, $request->all());
            
            return ApiResponseClass::sendResponse(new ClientResource($client), 'Cliente atualizado com sucesso', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao atualizar cliente');
        }
    }

    /**
     * @OA\Delete(
     *     path="/api/client/{id}",
     *     summary="Excluir cliente",
     *     description="Exclui um cliente do sistema",
     *     tags={"Cliente"},
     *     @OA\Parameter(
     *         name="id",
     *         in="path",
     *         description="ID do cliente",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Cliente excluído com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Cliente excluído com sucesso")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Cliente não encontrado"
     *     )
     * )
     */
    public function destroy($id): JsonResponse
    {
        try {
            [$_user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();
            // Verificar se o cliente existe e pertence ao tenant do usuário
            $existingClient = $this->clientService->getClientById($id);
            
            if (!$existingClient) {
                return ApiResponseClass::sendResponse(null, 'Cliente não encontrado', 404);
            }
            
            if ($existingClient->tenant_id !== $tenantId) {
                return ApiResponseClass::forbidden('Acesso negado ao cliente');
            }
            
            $guard = app(\App\Services\DeletionGuardService::class);
            $check = $guard->verificarVinculoPedidoAtivo($id, 'cliente', $tenantId);
            if ($check['blocked']) {
                return ApiResponseClass::conflict(
                    'Cliente não pode ser excluído, existe um pedido ativo ou não arquivado vinculado.',
                    ['orders' => $check['orders']]
                );
            }

            $result = $this->clientService->deleteClient($id);
            
            return ApiResponseClass::sendResponse(null, 'Cliente excluído com sucesso', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao excluir cliente');
        }
    }

    /**
     * @OA\Get(
     *     path="/api/client/stats",
     *     summary="Estatísticas de clientes",
     *     description="Retorna estatísticas dos clientes comparando com o mês anterior",
     *     tags={"Cliente"},
     *     @OA\Response(
     *         response=200,
     *         description="Estatísticas carregadas com sucesso",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="total_clients", type="object",
     *                     @OA\Property(property="current", type="integer", example=150),
     *                     @OA\Property(property="previous", type="integer", example=120),
     *                     @OA\Property(property="growth", type="number", format="float", example=25.0)
     *                 ),
     *                 @OA\Property(property="active_clients", type="object",
     *                     @OA\Property(property="current", type="integer", example=120),
     *                     @OA\Property(property="previous", type="integer", example=95),
     *                     @OA\Property(property="growth", type="number", format="float", example=26.3)
     *                 ),
     *                 @OA\Property(property="orders_per_client", type="object",
     *                     @OA\Property(property="current", type="number", format="float", example=8.5),
     *                     @OA\Property(property="previous", type="number", format="float", example=7.2),
     *                     @OA\Property(property="growth", type="number", format="float", example=18.1)
     *                 ),
     *                 @OA\Property(property="new_clients", type="object",
     *                     @OA\Property(property="current", type="integer", example=45),
     *                     @OA\Property(property="previous", type="integer", example=35),
     *                     @OA\Property(property="growth", type="number", format="float", example=28.6)
     *                 )
     *             ),
     *             @OA\Property(property="message", type="string", example="Estatísticas carregadas com sucesso")
     *         )
     *     )
     * )
     */
    public function stats(): JsonResponse
    {
        try {
            $user = Auth::user();;
            
            if (!$user) {
                return ApiResponseClass::unauthorized('Usuário não autenticado');
            }
            
            if (!$user->tenant_id) {
                return ApiResponseClass::forbidden('Usuário não possui tenant associado');
            }
            
            $stats = $this->clientService->getClientStats($user->tenant_id);
            return ApiResponseClass::sendResponse($stats, 'Estatísticas carregadas com sucesso', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao carregar estatísticas');
        }
    }

    /**
     * Check if CPF exists for tenant
     * GET /api/client/check-cpf?cpf={cpf}
     */
    public function checkCpf(CheckCpfRequest $request): JsonResponse
    {
        try {
            [$_user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();
            
            $cpf = $request->input('cpf');
            $existingClient = $this->clientService->findByCpfAndTenant($cpf, $tenantId);
            
            if ($existingClient) {
                return ApiResponseClass::sendResponse([
                    'exists' => true,
                    'client' => new ClientResource($existingClient),
                ], 'Cliente encontrado com este CPF', 200);
            }
            
            return ApiResponseClass::sendResponse([
                'exists' => false,
                'client' => null,
            ], 'CPF não encontrado', 200);
            
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao verificar CPF');
        }
    }
}