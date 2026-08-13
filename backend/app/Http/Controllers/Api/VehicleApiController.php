<?php

namespace App\Http\Controllers\Api;

use App\Classes\ApiResponseClass;
use App\Http\Controllers\Controller;
use App\Models\Vehicle;
use App\Services\AuthTenantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VehicleApiController extends Controller
{
    public function __construct(
        private readonly AuthTenantService $authTenantService,
    ) {}

    public function index(): JsonResponse
    {
        try {
            [$user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $vehicles = Vehicle::forTenant($tenantId)
                ->orderBy('plate')
                ->get();

            return ApiResponseClass::sendResponse($vehicles, 'Veículos recuperados', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao listar veículos');
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            [$user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $validated = $request->validate([
                'plate'        => 'required|string|max:10',
                'type'         => 'required|in:truck,van,motorcycle,car',
                'brand'        => 'nullable|string|max:100',
                'model'        => 'nullable|string|max:100',
                'year'         => 'nullable|integer|min:1900|max:2100',
                'capacity_kg'  => 'nullable|numeric|min:0',
                'capacity_m3'  => 'nullable|numeric|min:0',
                'km_per_liter' => 'nullable|numeric|min:0',
                'is_active'    => 'sometimes|boolean',
            ]);

            $plate = strtoupper(preg_replace('/[^A-Z0-9]/i', '', $validated['plate']));
            if (Vehicle::forTenant($tenantId)->where('plate', $plate)->exists()) {
                return ApiResponseClass::validationError(
                    ['plate' => ['Já existe um veículo com esta placa.']],
                    'Placa duplicada'
                );
            }

            $vehicle = Vehicle::create(array_merge($validated, [
                'tenant_id' => $tenantId,
                'plate'     => $plate,
            ]));

            return ApiResponseClass::sendResponse($vehicle, 'Veículo criado', 201);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao criar veículo');
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        try {
            [$user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $vehicle = Vehicle::forTenant($tenantId)->findOrFail($id);

            $validated = $request->validate([
                'plate'        => 'sometimes|string|max:10',
                'type'         => 'sometimes|in:truck,van,motorcycle,car',
                'brand'        => 'nullable|string|max:100',
                'model'        => 'nullable|string|max:100',
                'year'         => 'nullable|integer|min:1900|max:2100',
                'capacity_kg'  => 'nullable|numeric|min:0',
                'capacity_m3'  => 'nullable|numeric|min:0',
                'km_per_liter' => 'nullable|numeric|min:0',
                'is_active'    => 'sometimes|boolean',
            ]);

            if (isset($validated['plate'])) {
                $plate = strtoupper(preg_replace('/[^A-Z0-9]/i', '', $validated['plate']));
                $duplicate = Vehicle::forTenant($tenantId)
                    ->where('plate', $plate)
                    ->where('id', '!=', $id)
                    ->exists();

                if ($duplicate) {
                    return ApiResponseClass::validationError(
                        ['plate' => ['Já existe um veículo com esta placa.']],
                        'Placa duplicada'
                    );
                }
                $validated['plate'] = $plate;
            }

            $vehicle->update($validated);

            return ApiResponseClass::sendResponse($vehicle->fresh(), 'Veículo atualizado', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao atualizar veículo');
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            [$user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $vehicle = Vehicle::forTenant($tenantId)->findOrFail($id);

            if ($vehicle->shipments()->whereIn('status', ['draft', 'dispatched'])->exists()) {
                return ApiResponseClass::sendResponse(
                    null,
                    'Veículo possui romaneios ativos e não pode ser removido. Inative-o ao invés disso.',
                    422
                );
            }

            $vehicle->delete();

            return ApiResponseClass::sendResponse(null, 'Veículo removido', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao remover veículo');
        }
    }
}
