<?php

namespace App\Http\Controllers\Api;

use App\Classes\ApiResponseClass;
use App\Http\Controllers\Controller;
use App\Models\PriceTable;
use App\Services\AuthTenantService;
use App\Services\Commercial\PriceTableService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PriceTableApiController extends Controller
{
    public function __construct(
        private readonly AuthTenantService $authTenantService,
        private readonly PriceTableService $priceTableService,
    ) {}

    public function index(): JsonResponse
    {
        try {
            [$_user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();
            $tables = $this->priceTableService->listForTenant($tenantId);
            return ApiResponseClass::sendResponse($tables, 'Tabelas de preço recuperadas com sucesso', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao listar tabelas de preço');
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            [$_user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $validated = $request->validate([
                'name'                    => 'required|string|max:255',
                'description'             => 'nullable|string',
                'type'                    => 'sometimes|string|in:padrao,cliente_especifico,canal,volume',
                'is_active'               => 'sometimes|boolean',
                'items'                   => 'sometimes|array',
                'items.*.product_id'      => 'required_with:items|integer',
                'items.*.price'           => 'required_with:items|numeric|min:0',
                'items.*.min_quantity'    => 'sometimes|integer|min:1',
            ]);

            $table = $this->priceTableService->create($tenantId, $validated);

            return ApiResponseClass::sendResponse($table, 'Tabela de preço criada com sucesso', 201);
        } catch (ValidationException $ex) {
            throw $ex;
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao criar tabela de preço');
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            [$_user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $table = PriceTable::forTenant($tenantId)->with('items.product:id,name,sku')->find($id);

            if (!$table) {
                return ApiResponseClass::sendResponse(null, 'Tabela não encontrada', 404);
            }

            return ApiResponseClass::sendResponse($table, 'Tabela recuperada com sucesso', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao buscar tabela de preço');
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        try {
            [$_user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $table = PriceTable::forTenant($tenantId)->find($id);
            if (!$table) {
                return ApiResponseClass::sendResponse(null, 'Tabela não encontrada', 404);
            }

            $validated = $request->validate([
                'name'                    => 'sometimes|string|max:255',
                'description'             => 'nullable|string',
                'type'                    => 'sometimes|string|in:padrao,cliente_especifico,canal,volume',
                'is_active'               => 'sometimes|boolean',
                'items'                   => 'sometimes|array',
                'items.*.product_id'      => 'required_with:items|integer',
                'items.*.price'           => 'required_with:items|numeric|min:0',
                'items.*.min_quantity'    => 'sometimes|integer|min:1',
            ]);

            $table = $this->priceTableService->update($table, $validated);

            return ApiResponseClass::sendResponse($table, 'Tabela atualizada com sucesso', 200);
        } catch (ValidationException $ex) {
            throw $ex;
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao atualizar tabela de preço');
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            [$_user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $table = PriceTable::forTenant($tenantId)->find($id);
            if (!$table) {
                return ApiResponseClass::sendResponse(null, 'Tabela não encontrada', 404);
            }

            $table->items()->delete();
            $table->delete();

            return ApiResponseClass::sendResponse(null, 'Tabela excluída com sucesso', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao excluir tabela de preço');
        }
    }

    public function lookup(Request $request): JsonResponse
    {
        try {
            [$_user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $validated = $request->validate([
                'client_id'  => 'nullable|integer',
                'product_id' => 'required|integer',
                'quantity'   => 'sometimes|numeric|min:0.001',
            ]);

            $price = $this->priceTableService->lookupPrice(
                $validated['client_id'] ?? null,
                (int) $validated['product_id'],
                (float) ($validated['quantity'] ?? 1),
            );

            return ApiResponseClass::sendResponse([
                'product_id' => (int) $validated['product_id'],
                'price'      => $price,
            ], 'Preço consultado com sucesso', 200);
        } catch (ValidationException $ex) {
            throw $ex;
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao consultar preço');
        }
    }
}
