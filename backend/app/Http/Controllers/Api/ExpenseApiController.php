<?php

namespace App\Http\Controllers\Api;

use App\Classes\ApiResponseClass;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreExpenseRequest;
use App\Http\Resources\ExpenseResource;
use App\Services\AuthTenantService;
use App\Services\ExpenseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ExpenseApiController extends Controller
{
    public function __construct(
        private readonly ExpenseService $expenseService,
        private readonly AuthTenantService $authTenantService
    ) {}

    public function index(Request $request): JsonResponse
    {
        try {
            [$_user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $filters = [
                'status' => $request->query('status'),
                'category_id' => $request->query('category_id'),
                'supplier_id' => $request->query('supplier_id'),
                'start_date' => $request->query('start_date'),
                'end_date' => $request->query('end_date'),
            ];

            $expenses = $this->expenseService->getAllExpenses($tenantId, array_filter($filters));

            return ApiResponseClass::sendResponse(
                ExpenseResource::collection($expenses),
                'Despesas recuperadas com sucesso',
                200
            );
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao buscar despesas');
        }
    }

    public function store(StoreExpenseRequest $request): JsonResponse
    {
        try {
            [$_user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $expense = $this->expenseService->createExpense($request->validated(), $tenantId);

            return ApiResponseClass::sendResponse(
                new ExpenseResource($expense->load(['category', 'supplier', 'paymentMethod'])),
                'Despesa criada com sucesso',
                201
            );
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao criar despesa');
        }
    }

    public function show(string $uuid): JsonResponse
    {
        try {
            [$_user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $expense = $this->expenseService->getExpenseByUuid($uuid);

            if (!$expense || $expense->tenant_id !== $tenantId) {
                return ApiResponseClass::sendResponse(null, 'Despesa não encontrada', 404);
            }

            return ApiResponseClass::sendResponse(
                new ExpenseResource($expense),
                'Despesa recuperada com sucesso',
                200
            );
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao buscar despesa');
        }
    }

    public function update(StoreExpenseRequest $request, string $uuid): JsonResponse
    {
        try {
            [$_user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $expense = $this->expenseService->getExpenseByUuid($uuid);

            if (!$expense || $expense->tenant_id !== $tenantId) {
                return ApiResponseClass::sendResponse(null, 'Despesa não encontrada', 404);
            }

            $updated = $this->expenseService->updateExpense($expense->id, $request->validated());

            return ApiResponseClass::sendResponse(
                new ExpenseResource($updated),
                'Despesa atualizada com sucesso',
                200
            );
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao atualizar despesa');
        }
    }

    public function destroy(string $uuid): JsonResponse
    {
        try {
            [$_user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $expense = $this->expenseService->getExpenseByUuid($uuid);

            if (!$expense || $expense->tenant_id !== $tenantId) {
                return ApiResponseClass::sendResponse(null, 'Despesa não encontrada', 404);
            }

            $this->expenseService->deleteExpense($expense->id);

            return ApiResponseClass::sendResponse(null, 'Despesa excluída com sucesso', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao excluir despesa');
        }
    }

    public function uploadAttachment(Request $request, string $uuid): JsonResponse
    {
        try {
            $request->validate([
                'file' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'], // 5MB
            ]);

            [$_user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $expense = $this->expenseService->getExpenseByUuid($uuid);

            if (!$expense || $expense->tenant_id !== $tenantId) {
                return ApiResponseClass::sendResponse(null, 'Despesa não encontrada', 404);
            }

            $path = $this->expenseService->uploadAttachment($expense->id, $request->file('file'));

            return ApiResponseClass::sendResponse([
                'path' => $path,
                'url' => asset('storage/' . $path),
            ], 'Arquivo enviado com sucesso', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao enviar arquivo');
        }
    }

    public function stats(): JsonResponse
    {
        try {
            [$_user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $stats = $this->expenseService->getStats($tenantId);

            return ApiResponseClass::sendResponse($stats, 'Estatísticas recuperadas com sucesso', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao buscar estatísticas');
        }
    }
}

