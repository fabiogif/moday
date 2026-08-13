<?php

namespace App\Http\Controllers\Api;

use App\Classes\ApiResponseClass;
use App\Exceptions\Visit\VisitConflictException;
use App\Exceptions\Visit\VisitInvalidTransitionException;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Visit\ChangeVisitStatusRequest;
use App\Http\Resources\Visit\VisitResource;
use App\Services\AuthTenantService;
use App\Services\Visit\VisitService;
use Illuminate\Http\JsonResponse;

class VisitStatusApiController extends Controller
{
    public function __construct(
        private readonly AuthTenantService $authTenantService,
        private readonly VisitService $visitService,
    ) {
    }

    public function changeStatus(ChangeVisitStatusRequest $request, string $uuid): JsonResponse
    {
        try {
            [$user, $tenantId] = $this->authTenantService->requireAuthenticatedTenant();

            $visit = $this->visitService->find($tenantId, $uuid, []);
            if (!$visit) {
                return ApiResponseClass::sendResponse(null, 'Visita não encontrada', 404);
            }

            $validated = $request->validated();

            $updated = $this->visitService->changeStatus(
                $visit,
                $validated['status'],
                $validated['reason'] ?? null,
                $user->id,
                $validated['reschedule_to'] ?? null
            );

            return ApiResponseClass::sendResponse(new VisitResource($updated), 'Situação da visita atualizada com sucesso', 200);
        } catch (VisitInvalidTransitionException $ex) {
            return response()->json(['success' => false, 'message' => $ex->getMessage()], 422);
        } catch (VisitConflictException $ex) {
            return response()->json([
                'success' => false,
                'message' => $ex->getMessage(),
                'conflicting_visit' => new VisitResource($ex->getConflictingVisit()->loadMissing('client')),
            ], 409);
        } catch (\InvalidArgumentException $ex) {
            return response()->json(['success' => false, 'message' => $ex->getMessage()], 422);
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao alterar situação da visita');
        }
    }
}
