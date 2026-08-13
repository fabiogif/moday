<?php

namespace App\Http\Controllers\Api;

use App\Classes\ApiResponseClass;
use App\Exceptions\Visit\VisitConflictException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Visit\StoreVisitRequest;
use App\Http\Requests\Api\Visit\UpdateVisitRequest;
use App\Http\Resources\Visit\VisitResource;
use App\Models\AccountReceivable;
use App\Models\Client;
use App\Services\AuthTenantService;
use App\Services\Visit\VisitService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VisitApiController extends Controller
{
    public function __construct(
        private readonly AuthTenantService $authTenantService,
        private readonly VisitService $visitService,
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        try {
            [$user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $perPage = min((int) $request->get('per_page', 50), 100);
            $filters = $request->only([
                'date_from', 'date_to', 'user_id', 'region', 'city',
                'status', 'type', 'priority', 'client_id', 'search',
            ]);

            $paginated = $this->visitService->list($tenantId, $filters, $user, $perPage);

            return response()->json([
                'success' => true,
                'data' => VisitResource::collection($paginated->items()),
                'meta' => [
                    'current_page' => $paginated->currentPage(),
                    'last_page' => $paginated->lastPage(),
                    'per_page' => $paginated->perPage(),
                    'total' => $paginated->total(),
                ],
                'message' => 'Visitas listadas com sucesso',
            ], 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao listar visitas');
        }
    }

    public function show(string $uuid): JsonResponse
    {
        try {
            [, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $visit = $this->visitService->find($tenantId, $uuid);
            if (!$visit) {
                return ApiResponseClass::sendResponse(null, 'Visita não encontrada', 404);
            }

            $visit->client_alert = $this->buildClientAlert($visit->client);

            return ApiResponseClass::sendResponse(new VisitResource($visit), 'Visita recuperada com sucesso', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao buscar visita');
        }
    }

    public function store(StoreVisitRequest $request): JsonResponse
    {
        try {
            [$user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $validated = $request->validated();
            $force = (bool) ($validated['force'] ?? false);

            $result = $this->visitService->store($tenantId, $user->id, $validated, $force);

            return ApiResponseClass::sendResponse(
                new VisitResource($result['visit']),
                $result['warnings'] === [] ? 'Visita agendada com sucesso' : implode(' ', $result['warnings']),
                201
            );
        } catch (VisitConflictException $ex) {
            return response()->json([
                'success' => false,
                'message' => $ex->getMessage(),
                'conflicting_visit' => new VisitResource($ex->getConflictingVisit()->loadMissing('client')),
            ], 409);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao agendar visita');
        }
    }

    public function update(UpdateVisitRequest $request, string $uuid): JsonResponse
    {
        try {
            [$user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $visit = $this->visitService->find($tenantId, $uuid, []);
            if (!$visit) {
                return ApiResponseClass::sendResponse(null, 'Visita não encontrada', 404);
            }

            $validated = $request->validated();
            $force = (bool) ($validated['force'] ?? false);

            $result = $this->visitService->update($visit, $validated, $user->id, $force);

            return ApiResponseClass::sendResponse(
                new VisitResource($result['visit']),
                $result['warnings'] === [] ? 'Visita atualizada com sucesso' : implode(' ', $result['warnings']),
                200
            );
        } catch (VisitConflictException $ex) {
            return response()->json([
                'success' => false,
                'message' => $ex->getMessage(),
                'conflicting_visit' => new VisitResource($ex->getConflictingVisit()->loadMissing('client')),
            ], 409);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao atualizar visita');
        }
    }

    public function destroy(string $uuid): JsonResponse
    {
        try {
            [$user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $visit = $this->visitService->find($tenantId, $uuid, []);
            if (!$visit) {
                return ApiResponseClass::sendResponse(null, 'Visita não encontrada', 404);
            }

            $this->visitService->destroy($visit, $user->id);

            return ApiResponseClass::sendResponse(null, 'Visita excluída com sucesso', 200);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao excluir visita');
        }
    }

    /**
     * Nota: não reaproveita CreditLimitService::getOpenReceivableBalance() — esse método
     * filtra por status em inglês ('pending','overdue','partial') mas a tabela
     * accounts_receivable usa status em português ('pendente','vencido','parcial'),
     * então sempre retorna 0. Calculado aqui diretamente com os status corretos.
     */
    private function buildClientAlert(?Client $client): ?array
    {
        if (!$client) {
            return null;
        }

        $overdueAmount = (float) AccountReceivable::where('client_id', $client->id)
            ->where(function ($query) {
                $query->where('status', 'vencido')
                    ->orWhere(function ($sub) {
                        $sub->where('status', 'pendente')->where('due_date', '<', now());
                    });
            })
            ->selectRaw('COALESCE(SUM(amount - COALESCE(amount_received, 0)), 0) as balance')
            ->value('balance');

        $creditLimit = (float) ($client->credit_limit ?? 0);
        $openBalance = (float) AccountReceivable::where('client_id', $client->id)
            ->whereIn('status', ['pendente', 'vencido', 'parcial'])
            ->selectRaw('COALESCE(SUM(amount - COALESCE(amount_received, 0)), 0) as balance')
            ->value('balance');

        return [
            'overdue' => $overdueAmount > 0,
            'overdue_amount' => round($overdueAmount, 2),
            'credit_limit' => $creditLimit,
            'credit_available' => $creditLimit > 0 ? round(max($creditLimit - $openBalance, 0), 2) : null,
            'is_vip' => (bool) $client->is_vip,
        ];
    }
}
