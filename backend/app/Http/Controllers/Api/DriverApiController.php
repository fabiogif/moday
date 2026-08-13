<?php

namespace App\Http\Controllers\Api;

use App\Classes\ApiResponseClass;
use App\Http\Controllers\Controller;
use App\Models\Driver;
use App\Services\AuthTenantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DriverApiController extends Controller
{
    public function __construct(
        private readonly AuthTenantService $authTenantService,
    ) {}

    public function index(): JsonResponse
    {
        try {
            [$user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $drivers = Driver::forTenant($tenantId)
                ->orderBy('name')
                ->get();

            return ApiResponseClass::sendResponse($drivers, 'Motoristas recuperados', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao listar motoristas');
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            [$user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $validated = $request->validate([
                'name'           => 'required|string|max:200',
                'cpf'            => 'nullable|string|max:14',
                'phone'          => 'nullable|string|max:20',
                'license_number' => 'nullable|string|max:20',
                'license_expiry' => 'nullable|date',
                'is_active'      => 'sometimes|boolean',
            ]);

            if (!empty($validated['cpf'])) {
                $cpf = preg_replace('/\D/', '', $validated['cpf']);
                if (Driver::forTenant($tenantId)->where('cpf', $cpf)->exists()) {
                    return ApiResponseClass::validationError(
                        ['cpf' => ['Já existe um motorista com este CPF.']],
                        'CPF duplicado'
                    );
                }
                $validated['cpf'] = $cpf;
            }

            $driver = Driver::create(array_merge($validated, ['tenant_id' => $tenantId]));

            return ApiResponseClass::sendResponse($driver, 'Motorista criado', 201);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao criar motorista');
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        try {
            [$user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $driver = Driver::forTenant($tenantId)->findOrFail($id);

            $validated = $request->validate([
                'name'           => 'sometimes|string|max:200',
                'cpf'            => 'nullable|string|max:14',
                'phone'          => 'nullable|string|max:20',
                'license_number' => 'nullable|string|max:20',
                'license_expiry' => 'nullable|date',
                'is_active'      => 'sometimes|boolean',
            ]);

            if (!empty($validated['cpf'])) {
                $cpf = preg_replace('/\D/', '', $validated['cpf']);
                $duplicate = Driver::forTenant($tenantId)
                    ->where('cpf', $cpf)
                    ->where('id', '!=', $id)
                    ->exists();

                if ($duplicate) {
                    return ApiResponseClass::validationError(
                        ['cpf' => ['Já existe um motorista com este CPF.']],
                        'CPF duplicado'
                    );
                }
                $validated['cpf'] = $cpf;
            }

            $driver->update($validated);

            return ApiResponseClass::sendResponse($driver->fresh(), 'Motorista atualizado', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao atualizar motorista');
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            [$user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $driver = Driver::forTenant($tenantId)->findOrFail($id);

            if ($driver->shipments()->whereIn('status', ['draft', 'dispatched'])->exists()) {
                return ApiResponseClass::sendResponse(
                    null,
                    'Motorista possui romaneios ativos e não pode ser removido. Inative-o ao invés disso.',
                    422
                );
            }

            $driver->delete();

            return ApiResponseClass::sendResponse(null, 'Motorista removido', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao remover motorista');
        }
    }
}
