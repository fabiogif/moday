<?php

namespace App\Http\Controllers\Api;

use App\Classes\ApiResponseClass;
use App\Http\Controllers\Controller;
use App\Http\Requests\{StoreReviewRequest, ModerateReviewRequest};
use App\Http\Resources\ReviewResource;
use App\Repositories\Contracts\TenantRepositoryInterface;
use App\Services\ReviewService;
use Illuminate\Http\{Request, JsonResponse};
use Illuminate\Support\Facades\Log;

class ReviewApiController extends Controller
{
    public function __construct(
        private readonly ReviewService $reviewService,
        private readonly TenantRepositoryInterface $tenantRepository
    ) {}

    /**
     * [PÚBLICO] Listar avaliações aprovadas do cardápio
     * GET /api/public/store/{slug}/reviews
     */
    public function publicIndex(string $tenantSlug): JsonResponse
    {
        try {
            Log::info('ReviewApiController::publicIndex - Listando avaliações públicas', [
                'tenant_slug' => $tenantSlug
            ]);

            $tenant = $this->tenantRepository->getTenantBySlug($tenantSlug);
            if (!$tenant) {
                abort(404);
            }
            $reviews = $this->reviewService->getApprovedForPublic($tenant->id, 20);
            
            return ApiResponseClass::sendResponse(
                ReviewResource::collection($reviews),
                'Avaliações listadas com sucesso',
                200
            );
        } catch (\Exception $ex) {
            Log::error('ReviewApiController::publicIndex - Erro', [
                'message' => $ex->getMessage(),
                'tenant_slug' => $tenantSlug
            ]);
            return ApiResponseClass::rollback($ex, 'Erro ao listar avaliações');
        }
    }

    /**
     * [PÚBLICO] Obter avaliações em destaque
     * GET /api/public/store/{slug}/reviews/featured
     */
    public function publicFeatured(string $tenantSlug): JsonResponse
    {
        try {
            Log::info('ReviewApiController::publicFeatured - Listando avaliações em destaque', [
                'tenant_slug' => $tenantSlug
            ]);

            $tenant = $this->tenantRepository->getTenantBySlug($tenantSlug);
            if (!$tenant) {
                abort(404);
            }
            $reviews = $this->reviewService->getFeaturedForPublic($tenant->id, 5);
            
            return ApiResponseClass::sendResponse(
                ReviewResource::collection($reviews),
                'Avaliações em destaque listadas com sucesso',
                200
            );
        } catch (\Exception $ex) {
            Log::error('ReviewApiController::publicFeatured - Erro', [
                'message' => $ex->getMessage(),
                'tenant_slug' => $tenantSlug
            ]);
            return ApiResponseClass::rollback($ex, 'Erro ao listar avaliações em destaque');
        }
    }

    /**
     * [PÚBLICO] Obter estatísticas de avaliações
     * GET /api/public/store/{slug}/reviews/stats
     */
    public function publicStats(string $tenantSlug): JsonResponse
    {
        try {
            Log::info('ReviewApiController::publicStats - Buscando estatísticas', [
                'tenant_slug' => $tenantSlug
            ]);

            $tenant = $this->tenantRepository->getTenantBySlug($tenantSlug);
            if (!$tenant) {
                abort(404);
            }
            $stats = $this->reviewService->getStats($tenant->id);
            
            return ApiResponseClass::sendResponse(
                $stats,
                'Estatísticas listadas com sucesso',
                200
            );
        } catch (\Exception $ex) {
            Log::error('ReviewApiController::publicStats - Erro', [
                'message' => $ex->getMessage(),
                'tenant_slug' => $tenantSlug
            ]);
            return ApiResponseClass::rollback($ex, 'Erro ao buscar estatísticas');
        }
    }

    /**
     * [PÚBLICO] Criar nova avaliação
     * POST /api/public/reviews
     */
    public function store(StoreReviewRequest $request): JsonResponse
    {
        try {
            Log::info('ReviewApiController::store - Criando avaliação', [
                'order_id' => $request->input('order_id'),
                'rating' => $request->input('rating')
            ]);

            $data = $request->validated();
            $review = $this->reviewService->store($data);
            
            return ApiResponseClass::sendResponse(
                new ReviewResource($review),
                'Avaliação enviada! Será publicada após aprovação pela administração.',
                201
            );
        } catch (\Exception $ex) {
            Log::error('ReviewApiController::store - Erro', [
                'message' => $ex->getMessage(),
                'data' => $request->all()
            ]);
            return ApiResponseClass::rollback($ex, $ex->getMessage());
        }
    }

    /**
     * [ADMIN] Listar todas as avaliações
     * GET /api/reviews?status=all|pending|approved|rejected
     */
    public function index(Request $request): JsonResponse
    {
        try {
            /** @var \App\Models\User|null $user */
            $user = auth()->user();
            
            if (!$user) {
                Log::warning('ReviewApiController::index - Usuário não autenticado');
                return ApiResponseClass::unauthorized('Usuário não autenticado');
            }
            
            if (!$user->tenant_id) {
                Log::warning('ReviewApiController::index - Usuário sem tenant_id');
                return ApiResponseClass::forbidden('Usuário não possui tenant associado');
            }
            
            $status = $request->get('status', 'all');
            
            Log::info('ReviewApiController::index - Listando avaliações', [
                'status' => $status,
                'user_id' => $user->id,
                'tenant_id' => $user->tenant_id
            ]);

            $reviews = $this->reviewService->index($status);
            
            return ApiResponseClass::sendResponse(
                ReviewResource::collection($reviews),
                'Avaliações listadas com sucesso',
                200
            );
        } catch (\Exception $ex) {
            Log::error('ReviewApiController::index - Erro', [
                'message' => $ex->getMessage()
            ]);
            return ApiResponseClass::rollback($ex, 'Erro ao listar avaliações');
        }
    }

    /**
     * [ADMIN] Obter uma avaliação específica
     * GET /api/reviews/{uuid}
     */
    public function show(string $uuid): JsonResponse
    {
        try {
            Log::info('ReviewApiController::show - Buscando avaliação', ['uuid' => $uuid]);

            $review = $this->reviewService->getByUuid($uuid);
            
            if (!$review) {
                return ApiResponseClass::sendResponse([], 'Avaliação não encontrada', 404);
            }
            
            return ApiResponseClass::sendResponse(
                new ReviewResource($review),
                'Avaliação encontrada',
                200
            );
        } catch (\Exception $ex) {
            Log::error('ReviewApiController::show - Erro', [
                'message' => $ex->getMessage(),
                'uuid' => $uuid
            ]);
            return ApiResponseClass::rollback($ex, 'Erro ao buscar avaliação');
        }
    }

    /**
     * [ADMIN] Listar avaliações pendentes
     * GET /api/reviews/pending
     */
    public function pending(): JsonResponse
    {
        try {
            /** @var \App\Models\User|null $user */
            $user = auth()->user();
            
            if (!$user) {
                Log::warning('ReviewApiController::pending - Usuário não autenticado');
                return ApiResponseClass::unauthorized('Usuário não autenticado');
            }
            
            if (!$user->tenant_id) {
                Log::warning('ReviewApiController::pending - Usuário sem tenant_id');
                return ApiResponseClass::forbidden('Usuário não possui tenant associado');
            }
            
            Log::info('ReviewApiController::pending - Listando avaliações pendentes', [
                'user_id' => $user->id,
                'tenant_id' => $user->tenant_id
            ]);

            $reviews = $this->reviewService->getPendingForAdmin();
            
            return ApiResponseClass::sendResponse(
                ReviewResource::collection($reviews),
                'Avaliações pendentes listadas com sucesso',
                200
            );
        } catch (\Exception $ex) {
            Log::error('ReviewApiController::pending - Erro', [
                'message' => $ex->getMessage()
            ]);
            return ApiResponseClass::rollback($ex, 'Erro ao listar avaliações pendentes');
        }
    }

    /**
     * [ADMIN] Aprovar avaliação
     * POST /api/reviews/{uuid}/approve
     */
    public function approve(ModerateReviewRequest $request, string $uuid): JsonResponse
    {
        try {
            /** @var int|null $moderatorId */
            $moderatorId = auth()->id() ?? 0;
            $reason = $request->input('reason');
            
            Log::info('ReviewApiController::approve - Aprovando avaliação', [
                'uuid' => $uuid,
                'moderator_id' => $moderatorId
            ]);
            
            $review = $this->reviewService->approve($uuid, $moderatorId, $reason);
            
            return ApiResponseClass::sendResponse(
                new ReviewResource($review),
                'Avaliação aprovada com sucesso!',
                200
            );
        } catch (\Exception $ex) {
            Log::error('ReviewApiController::approve - Erro', [
                'message' => $ex->getMessage(),
                'uuid' => $uuid
            ]);
            return ApiResponseClass::rollback($ex, $ex->getMessage());
        }
    }

    /**
     * [ADMIN] Rejeitar avaliação
     * POST /api/reviews/{uuid}/reject
     */
    public function reject(ModerateReviewRequest $request, string $uuid): JsonResponse
    {
        try {
            /** @var int|null $moderatorId */
            $moderatorId = auth()->id() ?? 0;
            $reason = $request->input('reason', 'Conteúdo inadequado');
            
            Log::info('ReviewApiController::reject - Rejeitando avaliação', [
                'uuid' => $uuid,
                'moderator_id' => $moderatorId,
                'reason' => $reason
            ]);
            
            $review = $this->reviewService->reject($uuid, $moderatorId, $reason);
            
            return ApiResponseClass::sendResponse(
                new ReviewResource($review),
                'Avaliação rejeitada.',
                200
            );
        } catch (\Exception $ex) {
            Log::error('ReviewApiController::reject - Erro', [
                'message' => $ex->getMessage(),
                'uuid' => $uuid
            ]);
            return ApiResponseClass::rollback($ex, $ex->getMessage());
        }
    }

    /**
     * [ADMIN] Toggle destaque
     * POST /api/reviews/{uuid}/toggle-featured
     */
    public function toggleFeatured(string $uuid): JsonResponse
    {
        try {
            Log::info('ReviewApiController::toggleFeatured - Toggle destaque', ['uuid' => $uuid]);
            
            $review = $this->reviewService->toggleFeatured($uuid);
            
            $message = $review->is_featured 
                ? 'Avaliação marcada como destaque!' 
                : 'Avaliação removida dos destaques.';
            
            return ApiResponseClass::sendResponse(
                new ReviewResource($review),
                $message,
                200
            );
        } catch (\Exception $ex) {
            Log::error('ReviewApiController::toggleFeatured - Erro', [
                'message' => $ex->getMessage(),
                'uuid' => $uuid
            ]);
            return ApiResponseClass::rollback($ex, $ex->getMessage());
        }
    }

    /**
     * [ADMIN] Obter estatísticas
     * GET /api/reviews/stats
     */
    public function stats(): JsonResponse
    {
        try {
            /** @var \App\Models\User|null $user */
            $user = auth()->user();
            
            if (!$user) {
                Log::warning('ReviewApiController::stats - Usuário não autenticado');
                return ApiResponseClass::unauthorized('Usuário não autenticado');
            }
            
            if (!$user->tenant_id) {
                Log::warning('ReviewApiController::stats - Usuário sem tenant_id');
                return ApiResponseClass::forbidden('Usuário não possui tenant associado');
            }
            
            Log::info('ReviewApiController::stats - Buscando estatísticas', [
                'user_id' => $user->id,
                'tenant_id' => $user->tenant_id
            ]);

            $stats = $this->reviewService->getStats();
            
            return ApiResponseClass::sendResponse(
                $stats,
                'Estatísticas obtidas com sucesso',
                200
            );
        } catch (\Exception $ex) {
            Log::error('ReviewApiController::stats - Erro', [
                'message' => $ex->getMessage()
            ]);
            return ApiResponseClass::rollback($ex, 'Erro ao buscar estatísticas');
        }
    }

    /**
     * [ADMIN] Obter avaliações recentes para dashboard
     * GET /api/reviews/recent
     */
    public function recent(Request $request): JsonResponse
    {
        try {
            /** @var \App\Models\User|null $user */
            $user = auth()->user();
            
            if (!$user) {
                Log::warning('ReviewApiController::recent - Usuário não autenticado');
                return ApiResponseClass::unauthorized('Usuário não autenticado');
            }
            
            if (!$user->tenant_id) {
                Log::warning('ReviewApiController::recent - Usuário sem tenant_id');
                return ApiResponseClass::forbidden('Usuário não possui tenant associado');
            }
            
            $limit = $request->get('limit', 5);
            
            Log::info('ReviewApiController::recent - Buscando avaliações recentes', [
                'limit' => $limit,
                'user_id' => $user->id,
                'tenant_id' => $user->tenant_id
            ]);

            $reviews = $this->reviewService->getRecentForDashboard($limit);
            
            return ApiResponseClass::sendResponse(
                ReviewResource::collection($reviews),
                'Avaliações recentes listadas com sucesso',
                200
            );
        } catch (\Exception $ex) {
            Log::error('ReviewApiController::recent - Erro', [
                'message' => $ex->getMessage()
            ]);
            return ApiResponseClass::rollback($ex, 'Erro ao buscar avaliações recentes');
        }
    }

    /**
     * [ADMIN] Deletar avaliação
     * DELETE /api/reviews/{uuid}
     */
    public function destroy(string $uuid): JsonResponse
    {
        try {
            /** @var int|null $userId */
            $userId = auth()->id();
            
            Log::info('ReviewApiController::destroy - Deletando avaliação', [
                'uuid' => $uuid,
                'user_id' => $userId
            ]);

            $this->reviewService->delete($uuid);
            
            return ApiResponseClass::sendResponse(
                [],
                'Avaliação removida com sucesso.',
                200
            );
        } catch (\Exception $ex) {
            Log::error('ReviewApiController::destroy - Erro', [
                'message' => $ex->getMessage(),
                'uuid' => $uuid
            ]);
            return ApiResponseClass::rollback($ex, $ex->getMessage());
        }
    }

    /**
     * [PÚBLICO] Incrementar contador de útil
     * POST /api/public/reviews/{uuid}/helpful
     */
    public function incrementHelpful(string $uuid): JsonResponse
    {
        try {
            Log::info('ReviewApiController::incrementHelpful - Incrementando contador', ['uuid' => $uuid]);

            $review = $this->reviewService->incrementHelpful($uuid);
            
            return ApiResponseClass::sendResponse(
                ['helpful_count' => $review->helpful_count],
                'Obrigado pelo feedback!',
                200
            );
        } catch (\Exception $ex) {
            Log::error('ReviewApiController::incrementHelpful - Erro', [
                'message' => $ex->getMessage(),
                'uuid' => $uuid
            ]);
            return ApiResponseClass::rollback($ex, $ex->getMessage());
        }
    }
}
