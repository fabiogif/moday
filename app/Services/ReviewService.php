<?php

namespace App\Services;

use App\Repositories\Contracts\ReviewRepositoryInterface;
use App\Models\Review;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Log;

readonly class ReviewService
{
    public function __construct(
        private ReviewRepositoryInterface $reviewRepository
    ) {}

    /**
     * Criar nova avaliação
     */
    public function store(array $data): Review
    {
        Log::info('ReviewService::store - Criando avaliação', [
            'order_id' => $data['order_id'] ?? null,
            'rating' => $data['rating'] ?? null
        ]);

        // Verificar se já existe avaliação para este pedido
        if (isset($data['order_id'])) {
            $existing = $this->reviewRepository->getByOrderId($data['order_id']);
            if ($existing) {
                throw new \Exception('Este pedido já possui uma avaliação.');
            }
        }
        
        // Sempre criar como pending
        $data['status'] = 'pending';
        
        return $this->reviewRepository->store($data);
    }

    /**
     * Aprovar avaliação
     */
    public function approve(string $uuid, int $moderatorId, ?string $reason = null): Review
    {
        Log::info('ReviewService::approve - Aprovando avaliação', [
            'uuid' => $uuid,
            'moderator_id' => $moderatorId
        ]);

        $review = $this->reviewRepository->approve($uuid, $moderatorId, $reason);
        
        if (!$review) {
            throw new \Exception('Avaliação não encontrada.');
        }
        
        return $review;
    }

    /**
     * Rejeitar avaliação
     */
    public function reject(string $uuid, int $moderatorId, ?string $reason = null): Review
    {
        Log::info('ReviewService::reject - Rejeitando avaliação', [
            'uuid' => $uuid,
            'moderator_id' => $moderatorId,
            'reason' => $reason
        ]);

        $review = $this->reviewRepository->reject($uuid, $moderatorId, $reason);
        
        if (!$review) {
            throw new \Exception('Avaliação não encontrada.');
        }
        
        return $review;
    }

    /**
     * Toggle destaque
     */
    public function toggleFeatured(string $uuid): Review
    {
        Log::info('ReviewService::toggleFeatured - Toggle destaque', ['uuid' => $uuid]);

        $review = $this->reviewRepository->toggleFeatured($uuid);
        
        if (!$review) {
            throw new \Exception('Avaliação não encontrada.');
        }
        
        // Apenas avaliações aprovadas podem ser destaque
        if ($review->status !== 'approved' && $review->is_featured) {
            $review->unmarkAsFeatured();
            throw new \Exception('Apenas avaliações aprovadas podem ser destacadas.');
        }
        
        return $review;
    }

    /**
     * Deletar avaliação
     */
    public function delete(string $uuid): bool
    {
        Log::info('ReviewService::delete - Deletando avaliação', ['uuid' => $uuid]);

        $deleted = $this->reviewRepository->deleteByUuid($uuid);
        
        if (!$deleted) {
            throw new \Exception('Avaliação não encontrada.');
        }
        
        return true;
    }

    /**
     * Listar todas as avaliações de um tenant (com filtro opcional)
     */
    public function index(?string $status = null): Collection
    {
        /** @var \App\Models\User|null $user */
        $user = auth()->user();
        
        Log::info('ReviewService::index - Listando avaliações', [
            'user_id' => $user ? $user->id : null,
            'tenant_id' => $user ? $user->tenant_id : null,
            'status' => $status
        ]);
        
        if (!$user || !$user->tenant_id) {
            Log::warning('ReviewService::index - Usuário não autenticado ou sem tenant');
            return new Collection();
        }
        
        return $this->reviewRepository->getAllForTenant($user->tenant_id, $status);
    }

    /**
     * Obter avaliações aprovadas (público)
     */
    public function getApprovedForPublic(int $tenantId, int $limit = 10): Collection
    {
        return $this->reviewRepository->getApprovedForTenant($tenantId, $limit);
    }

    /**
     * Obter avaliações em destaque (público)
     */
    public function getFeaturedForPublic(int $tenantId, int $limit = 5): Collection
    {
        return $this->reviewRepository->getFeaturedForTenant($tenantId, $limit);
    }

    /**
     * Obter avaliações pendentes (admin)
     */
    public function getPendingForAdmin(): Collection
    {
        /** @var \App\Models\User|null $user */
        $user = auth()->user();
        
        if (!$user || !$user->tenant_id) {
            return new Collection();
        }
        
        return $this->reviewRepository->getPendingForTenant($user->tenant_id);
    }

    /**
     * Obter estatísticas
     */
    public function getStats(?int $tenantId = null): array
    {
        if (!$tenantId) {
            /** @var \App\Models\User|null $user */
            $user = auth()->user();
            if (!$user || !$user->tenant_id) {
                return [
                    'total' => 0,
                    'average_rating' => 0,
                    'rating_distribution' => ['5' => 0, '4' => 0, '3' => 0, '2' => 0, '1' => 0],
                    'rating_percentages' => ['5' => 0, '4' => 0, '3' => 0, '2' => 0, '1' => 0],
                    'pending_count' => 0,
                    'approved_count' => 0,
                    'rejected_count' => 0,
                    'featured_count' => 0,
                ];
            }
            $tenantId = $user->tenant_id;
        }
        
        return $this->reviewRepository->getStats($tenantId);
    }

    /**
     * Obter últimas avaliações para dashboard
     */
    public function getRecentForDashboard(int $limit = 5): Collection
    {
        /** @var \App\Models\User|null $user */
        $user = auth()->user();
        
        if (!$user || !$user->tenant_id) {
            return new Collection();
        }
        
        return $this->reviewRepository->getRecentForDashboard($user->tenant_id, $limit);
    }

    /**
     * Buscar avaliação por UUID
     */
    public function getByUuid(string $uuid): ?Review
    {
        return $this->reviewRepository->findByUuid($uuid);
    }

    /**
     * Incrementar contador de útil
     */
    public function incrementHelpful(string $uuid): Review
    {
        Log::info('ReviewService::incrementHelpful - Incrementando contador', ['uuid' => $uuid]);

        $review = $this->reviewRepository->findByUuid($uuid);
        
        if (!$review) {
            throw new \Exception('Avaliação não encontrada.');
        }
        
        $review->incrementHelpful();
        
        return $review->fresh();
    }
}
