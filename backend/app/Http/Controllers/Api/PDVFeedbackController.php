<?php

namespace App\Http\Controllers\Api;

use App\Classes\ApiResponseClass;
use App\Http\Requests\Api\StorePDVFeedbackRequest;
use App\Http\Requests\Api\UpdatePDVFeedbackStatusRequest;
use App\Http\Resources\PDVFeedbackResource;
use App\Services\PDVFeedbackService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PDVFeedbackController extends Controller
{
    public function __construct(
        protected PDVFeedbackService $feedbackService
    ) {
    }

    /**
     * Criar novo feedback do PDV
     */
    public function store(StorePDVFeedbackRequest $request): JsonResponse
    {
        try {
            $tenantId = Auth::user()?->tenant_id;
            
            if (!$tenantId) {
                return ApiResponseClass::sendResponse('', 'Usuário não possui tenant associado', 400);
            }

            $userId = Auth::id();

            // Coletar metadata adicional (versão do navegador, etc)
            $metadata = [
                'user_agent' => $request->userAgent(),
                'ip' => $request->ip(),
                'timestamp' => now()->toIso8601String(),
            ];

            if ($request->has('metadata') && is_array($request->metadata)) {
                $metadata = array_merge($metadata, $request->metadata);
            }

            $feedback = $this->feedbackService->createFeedback(
                array_merge($request->validated(), ['metadata' => $metadata]),
                $tenantId,
                $userId
            );

            return ApiResponseClass::sendResponse(
                new PDVFeedbackResource($feedback),
                'Feedback enviado com sucesso! Obrigado pela sua contribuição.',
                201
            );
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao enviar feedback');
        }
    }

    /**
     * Listar feedbacks do tenant (apenas para admin)
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $tenantId = Auth::user()?->tenant_id;
            
            if (!$tenantId) {
                return ApiResponseClass::sendResponse('', 'Usuário não possui tenant associado', 400);
            }

            $type = $request->query('type');
            $status = $request->query('status');
            $page = (int) $request->query('page', 1);
            $perPage = (int) $request->query('per_page', 15);

            $feedbacks = $this->feedbackService->getFeedbacksByTenant(
                $tenantId,
                $type,
                $status,
                $page,
                $perPage
            );

            return ApiResponseClass::sendResponsePaginate(
                PDVFeedbackResource::class,
                $feedbacks,
                200
            );
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao listar feedbacks');
        }
    }

    /**
     * Buscar feedback por UUID
     */
    public function show(string $uuid): JsonResponse
    {
        try {
            $tenantId = Auth::user()?->tenant_id;
            
            if (!$tenantId) {
                return ApiResponseClass::sendResponse('', 'Usuário não possui tenant associado', 400);
            }

            $feedback = $this->feedbackService->getFeedbackByUuid($uuid, $tenantId);

            if (!$feedback) {
                return ApiResponseClass::sendResponse('', 'Feedback não encontrado', 404);
            }

            return ApiResponseClass::sendResponse(
                new PDVFeedbackResource($feedback),
                'Feedback encontrado',
                200
            );
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao buscar feedback');
        }
    }

    /**
     * Atualizar status do feedback (apenas para admin)
     */
    public function updateStatus(UpdatePDVFeedbackStatusRequest $request, string $uuid): JsonResponse
    {
        try {
            $tenantId = Auth::user()?->tenant_id;
            
            if (!$tenantId) {
                return ApiResponseClass::sendResponse('', 'Usuário não possui tenant associado', 400);
            }

            $reviewedBy = Auth::id();
            $reviewNotes = $request->input('review_notes');

            $updated = $this->feedbackService->updateFeedbackStatus(
                $uuid,
                $tenantId,
                $request->validated()['status'],
                $reviewedBy,
                $reviewNotes
            );

            if (!$updated) {
                return ApiResponseClass::sendResponse('', 'Feedback não encontrado', 404);
            }

            $feedback = $this->feedbackService->getFeedbackByUuid($uuid, $tenantId);

            return ApiResponseClass::sendResponse(
                new PDVFeedbackResource($feedback),
                'Status do feedback atualizado com sucesso',
                200
            );
        } catch (\Exception $ex) {
            return ApiResponseClass::rollback($ex, 'Erro ao atualizar status do feedback');
        }
    }
}

