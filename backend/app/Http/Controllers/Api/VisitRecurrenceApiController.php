<?php

namespace App\Http\Controllers\Api;

use App\Classes\ApiResponseClass;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Visit\StoreVisitRecurrenceRequest;
use App\Http\Requests\Api\Visit\UpdateVisitRecurrenceRequest;
use App\Http\Resources\Visit\VisitRecurrenceResource;
use App\Http\Resources\Visit\VisitResource;
use App\Services\AuthTenantService;
use App\Services\Visit\VisitRecurrenceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VisitRecurrenceApiController extends Controller
{
    public function __construct(
        private readonly AuthTenantService $authTenantService,
        private readonly VisitRecurrenceService $recurrenceService,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        try {
            [$user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $perPage = min((int) $request->get('per_page', 50), 100);
            $filters = $request->only(['user_id', 'client_id', 'is_active']);

            $paginated = $this->recurrenceService->list($tenantId, $filters, $user, $perPage);

            return response()->json([
                'success' => true,
                'data' => VisitRecurrenceResource::collection($paginated->items()),
                'meta' => [
                    'current_page' => $paginated->currentPage(),
                    'last_page' => $paginated->lastPage(),
                    'per_page' => $paginated->perPage(),
                    'total' => $paginated->total(),
                ],
                'message' => 'Recorrências listadas com sucesso',
            ], 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao listar recorrências');
        }
    }

    public function show(string $uuid): JsonResponse
    {
        try {
            [, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $recurrence = $this->recurrenceService->find($tenantId, $uuid);
            if (!$recurrence) {
                return ApiResponseClass::sendResponse(null, 'Recorrência não encontrada', 404);
            }

            return ApiResponseClass::sendResponse(new VisitRecurrenceResource($recurrence->load(['client', 'user'])), 'Recorrência recuperada com sucesso', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao buscar recorrência');
        }
    }

    public function store(StoreVisitRecurrenceRequest $request): JsonResponse
    {
        try {
            [$user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $recurrence = $this->recurrenceService->store($tenantId, $user->id, $request->validated());

            return ApiResponseClass::sendResponse(new VisitRecurrenceResource($recurrence), 'Recorrência criada com sucesso', 201);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao criar recorrência');
        }
    }

    public function update(UpdateVisitRecurrenceRequest $request, string $uuid): JsonResponse
    {
        try {
            [, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $recurrence = $this->recurrenceService->find($tenantId, $uuid);
            if (!$recurrence) {
                return ApiResponseClass::sendResponse(null, 'Recorrência não encontrada', 404);
            }

            $updated = $this->recurrenceService->update($recurrence, $request->validated());

            return ApiResponseClass::sendResponse(new VisitRecurrenceResource($updated), 'Recorrência atualizada com sucesso', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao atualizar recorrência');
        }
    }

    /**
     * Não remove o registro (visitas já geradas referenciam a recorrência via
     * recurrence_id) — apenas desativa, parando a geração de novas ocorrências.
     */
    public function destroy(string $uuid): JsonResponse
    {
        try {
            [, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $recurrence = $this->recurrenceService->find($tenantId, $uuid);
            if (!$recurrence) {
                return ApiResponseClass::sendResponse(null, 'Recorrência não encontrada', 404);
            }

            $this->recurrenceService->deactivate($recurrence);

            return ApiResponseClass::sendResponse(null, 'Recorrência desativada com sucesso', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao desativar recorrência');
        }
    }

    public function generate(Request $request, string $uuid): JsonResponse
    {
        try {
            [$user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $recurrence = $this->recurrenceService->find($tenantId, $uuid);
            if (!$recurrence) {
                return ApiResponseClass::sendResponse(null, 'Recorrência não encontrada', 404);
            }

            $validated = $request->validate([
                'days' => ['nullable', 'integer', 'min:1', 'max:' . VisitRecurrenceService::MAX_GENERATION_WINDOW_DAYS],
            ]);
            $days = (int) ($validated['days'] ?? VisitRecurrenceService::DEFAULT_GENERATION_WINDOW_DAYS);

            $result = $this->recurrenceService->generateOccurrences($recurrence, $days, $user->id);

            return ApiResponseClass::sendResponse([
                'created' => VisitResource::collection($result['created']),
                'skipped' => $result['skipped'],
            ], sprintf('%d visita(s) geradas, %d pulada(s) por conflito', count($result['created']), count($result['skipped'])), 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao gerar ocorrências da recorrência');
        }
    }
}
