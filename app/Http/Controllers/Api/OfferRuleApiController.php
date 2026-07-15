<?php

namespace App\Http\Controllers\Api;

use App\Classes\ApiResponseClass;
use App\Http\Controllers\Controller;
use App\Models\OfferRule;
use App\Services\AuthTenantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OfferRuleApiController extends Controller
{
    public function __construct(
        private readonly AuthTenantService $authTenantService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        try {
            [, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $perPage = min((int) $request->get('per_page', 20), 100);

            $rules = OfferRule::query()
                ->where('tenant_id', $tenantId)
                ->with('products.product:id,name')
                ->orderByDesc('priority')
                ->orderByDesc('id')
                ->paginate($perPage);

            return response()->json([
                'success' => true,
                'data'    => $rules->items(),
                'meta'    => [
                    'current_page' => $rules->currentPage(),
                    'last_page'    => $rules->lastPage(),
                    'per_page'     => $rules->perPage(),
                    'total'        => $rules->total(),
                ],
                'message' => 'Ofertas listadas com sucesso',
            ]);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao listar ofertas');
        }
    }

    public function store(Request $request): JsonResponse
    {
        try {
            [, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $validated = $this->validatePayload($request);
            $this->validateProductsForType($validated['type'], $validated['products'], $validated['discount_percent'] ?? null);

            $rule = DB::transaction(function () use ($tenantId, $validated) {
                $rule = OfferRule::create([
                    'tenant_id'        => $tenantId,
                    'name'             => $validated['name'],
                    'description'      => $validated['description'] ?? null,
                    'type'             => $validated['type'],
                    'discount_percent' => $validated['discount_percent'] ?? null,
                    'is_active'        => $validated['is_active'] ?? true,
                    'starts_at'        => $validated['starts_at'] ?? null,
                    'ends_at'          => $validated['ends_at'] ?? null,
                    'priority'         => $validated['priority'] ?? 0,
                ]);

                $rule->products()->createMany($validated['products']);

                return $rule;
            });

            $rule->load('products.product:id,name');

            return ApiResponseClass::sendResponse($rule, 'Oferta criada com sucesso', 201);
        } catch (\DomainException $ex) {
            return ApiResponseClass::sendResponse('', $ex->getMessage(), 422);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao criar oferta');
        }
    }

    public function show(int $id): JsonResponse
    {
        try {
            [, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $rule = OfferRule::query()
                ->where('tenant_id', $tenantId)
                ->with('products.product:id,name')
                ->find($id);

            if (!$rule) {
                return ApiResponseClass::sendResponse('', 'Oferta não encontrada', 404);
            }

            return ApiResponseClass::sendResponse($rule, 'Oferta encontrada');
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao buscar oferta');
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        try {
            [, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $rule = OfferRule::query()->where('tenant_id', $tenantId)->find($id);
            if (!$rule) {
                return ApiResponseClass::sendResponse('', 'Oferta não encontrada', 404);
            }

            $validated = $this->validatePayload($request);
            $this->validateProductsForType($validated['type'], $validated['products'], $validated['discount_percent'] ?? null);

            DB::transaction(function () use ($rule, $validated) {
                $rule->update([
                    'name'             => $validated['name'],
                    'description'      => $validated['description'] ?? null,
                    'type'             => $validated['type'],
                    'discount_percent' => $validated['discount_percent'] ?? null,
                    'is_active'        => $validated['is_active'] ?? true,
                    'starts_at'        => $validated['starts_at'] ?? null,
                    'ends_at'          => $validated['ends_at'] ?? null,
                    'priority'         => $validated['priority'] ?? 0,
                ]);

                $rule->products()->delete();
                $rule->products()->createMany($validated['products']);
            });

            $rule->load('products.product:id,name');

            return ApiResponseClass::sendResponse($rule, 'Oferta atualizada com sucesso');
        } catch (\DomainException $ex) {
            return ApiResponseClass::sendResponse('', $ex->getMessage(), 422);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao atualizar oferta');
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            [, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $rule = OfferRule::query()->where('tenant_id', $tenantId)->find($id);
            if (!$rule) {
                return ApiResponseClass::sendResponse('', 'Oferta não encontrada', 404);
            }

            $rule->delete();

            return ApiResponseClass::sendResponse('', 'Oferta removida com sucesso');
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao remover oferta');
        }
    }

    private function validatePayload(Request $request): array
    {
        return $request->validate([
            'name'                     => 'required|string|max:150',
            'description'              => 'nullable|string',
            'type'                     => 'required|string|in:quantity_discount,combo,cross_sell',
            'discount_percent'         => 'nullable|numeric|min:0|max:100',
            'is_active'                => 'sometimes|boolean',
            'starts_at'                => 'nullable|date',
            'ends_at'                  => 'nullable|date|after_or_equal:starts_at',
            'priority'                 => 'sometimes|integer|min:0',
            'products'                 => 'required|array|min:1',
            'products.*.product_id'    => 'required|integer',
            'products.*.role'         => 'required|string|in:trigger,result',
            'products.*.min_quantity'  => 'sometimes|integer|min:1',
        ]);
    }

    private function validateProductsForType(string $type, array $products, ?float $discountPercent): void
    {
        $triggers = array_values(array_filter($products, fn ($p) => $p['role'] === 'trigger'));
        $results  = array_values(array_filter($products, fn ($p) => $p['role'] === 'result'));

        match ($type) {
            'quantity_discount' => $this->assert(
                count($triggers) === 1 && count($results) === 0,
                'Desconto por quantidade precisa de exatamente 1 produto gatilho e nenhum produto resultado.'
            ),
            'combo' => $this->assert(
                count($triggers) >= 2 && count($results) === 0,
                'Combo precisa de pelo menos 2 produtos gatilho e nenhum produto resultado.'
            ),
            'cross_sell' => $this->assert(
                count($triggers) === 1 && count($results) === 1,
                'Cross-sell precisa de exatamente 1 produto gatilho e 1 produto sugerido.'
            ),
            default => null,
        };

        if (in_array($type, ['quantity_discount', 'combo'], true)) {
            $this->assert($discountPercent !== null, 'Informe o percentual de desconto para este tipo de oferta.');
        }
    }

    private function assert(bool $condition, string $message): void
    {
        if (!$condition) {
            throw new \DomainException($message);
        }
    }
}
