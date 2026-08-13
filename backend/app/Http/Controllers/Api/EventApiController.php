<?php

namespace App\Http\Controllers\Api;

use App\Classes\ApiResponseClass;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\StoreEventRequest;
use App\Http\Requests\Api\UpdateEventRequest;
use App\Http\Resources\EventResource;
use App\Services\EventService;
use App\Services\AuthTenantService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Auth;

class EventApiController extends Controller
{
    public function __construct(
        private readonly EventService $eventService,
        private readonly AuthTenantService $authTenantService
    ) {}

    /**
     * List all events for tenant
     * GET /api/events
     */
    public function index(Request $request): JsonResponse
    {
        try {
            [$_user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            // Se fornecido range de datas, filtrar
            if ($request->has('start_date') && $request->has('end_date')) {
                $startDate = $request->input('start_date');
                $endDate = $request->input('end_date');
                
                // Validar formato de data
                $startTimestamp = strtotime($startDate);
                $endTimestamp = strtotime($endDate);
                
                if (!$startTimestamp || !$endTimestamp) {
                    return ApiResponseClass::sendResponse(
                        '',
                        'Formato de data inválido. Use Y-m-d H:i:s',
                        422
                    );
                }
                
                // Validar que end_date é posterior a start_date
                if ($endTimestamp < $startTimestamp) {
                    return ApiResponseClass::sendResponse(
                        '',
                        'A data final deve ser posterior à data inicial',
                        422
                    );
                }
                
                // Limitar range máximo (1 ano em segundos)
                $maxRange = 365 * 24 * 60 * 60;
                if (($endTimestamp - $startTimestamp) > $maxRange) {
                    return ApiResponseClass::sendResponse(
                        '',
                        'O intervalo de datas não pode ser maior que 1 ano',
                        422
                    );
                }
                
                $events = $this->eventService->getByDateRange(
                    $tenantId,
                    $startDate,
                    $endDate
                );
            } else {
                $events = $this->eventService->getAllByTenant($tenantId);
            }

            // Log estruturado para rastreabilidade
            $user = Auth::user();
            Log::info('Eventos listados', [
                'user_id' => $user ? $user->id : null,
                'tenant_id' => $tenantId,
                'has_date_filter' => $request->has('start_date'),
                'count' => $events->count(),
                'request_id' => $request->header('X-Request-ID'),
            ]);

            return ApiResponseClass::sendResponse(
                EventResource::collection($events),
                'Eventos carregados com sucesso',
                200
            );
        } catch (\Exception $ex) {
            // Log de erro melhorado
            $user = Auth::user();
            Log::error('Erro ao carregar eventos', [
                'user_id' => $user ? $user->id : null,
                'tenant_id' => $tenantId ?? null,
                'error' => $ex->getMessage(),
                'trace' => $ex->getTraceAsString(),
                'request_id' => $request->header('X-Request-ID'),
            ]);
            
            return ApiResponseClass::rollback($ex, 'Erro ao carregar eventos');
        }
    }

    /**
     * Get event statistics
     * GET /api/events/stats
     */
    public function stats(): JsonResponse
    {
        try {
            [$_user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();
            $stats = $this->eventService->getStats($tenantId);

            return ApiResponseClass::sendResponse($stats, 'Estatísticas carregadas com sucesso', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao carregar estatísticas');
        }
    }

    /**
     * Get upcoming events
     * GET /api/events/upcoming
     */
    public function upcoming(Request $request): JsonResponse
    {
        try {
            [$_user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();
            
            // Validar e limitar parâmetro limit para prevenir DoS
            $limit = (int) $request->input('limit', 10);
            $limit = min($limit, 100); // Máximo 100
            $limit = max($limit, 1);   // Mínimo 1
            
            $events = $this->eventService->getUpcoming($tenantId, $limit);

            return ApiResponseClass::sendResponse(
                EventResource::collection($events),
                'Próximos eventos carregados com sucesso',
                200
            );
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao carregar próximos eventos');
        }
    }

    /**
     * Show specific event
     * GET /api/events/{uuid}
     */
    public function show(string $uuid): JsonResponse
    {
        try {
            [$_user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();
            $event = $this->eventService->getByUuid($uuid, $tenantId);

            if (!$event) {
                return ApiResponseClass::sendResponse('', 'Evento não encontrado', 404);
            }

            return ApiResponseClass::sendResponse(
                new EventResource($event),
                'Evento carregado com sucesso',
                200
            );
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao carregar evento');
        }
    }

    /**
     * Create new event
     * POST /api/events
     */
    public function store(StoreEventRequest $request): JsonResponse
    {
        try {
            [$_user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();
            $data = $request->validated();

            $event = $this->eventService->create($data, $tenantId);

            return ApiResponseClass::sendResponse(
                new EventResource($event),
                'Evento criado com sucesso',
                201
            );
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao criar evento');
        }
    }

    /**
     * Update existing event
     * PUT /api/events/{uuid}
     */
    public function update(UpdateEventRequest $request, string $uuid): JsonResponse
    {
        try {
            [$_user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();
            $data = $request->validated();

            $event = $this->eventService->update($uuid, $data, $tenantId);

            if (!$event) {
                return ApiResponseClass::sendResponse('', 'Evento não encontrado', 404);
            }

            return ApiResponseClass::sendResponse(
                new EventResource($event),
                'Evento atualizado com sucesso',
                200
            );
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao atualizar evento');
        }
    }

    /**
     * Delete event
     * DELETE /api/events/{uuid}
     */
    public function destroy(string $uuid): JsonResponse
    {
        try {
            [$_user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();
            $result = $this->eventService->delete($uuid, $tenantId);

            if (!$result) {
                return ApiResponseClass::sendResponse('', 'Evento não encontrado', 404);
            }

            return ApiResponseClass::sendResponse('', 'Evento excluído com sucesso', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao excluir evento');
        }
    }
}

