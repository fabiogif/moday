<?php

namespace App\Repositories;

use App\Models\Review;
use App\Repositories\Contracts\ReviewRepositoryInterface;
use Illuminate\Database\Eloquent\{Collection, Model};

class ReviewRepository extends BaseRepository implements ReviewRepositoryInterface
{
    public function __construct(protected Model $entity = new Review())
    {
    }

    /**
     * Obter avaliações aprovadas para um tenant
     */
    public function getApprovedForTenant(int $tenantId, int $limit = 10): Collection
    {
        return $this->entity
            ->forTenant($tenantId)
            ->approved()
            ->with(['order', 'user'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Obter avaliações em destaque para um tenant
     */
    public function getFeaturedForTenant(int $tenantId, int $limit = 5): Collection
    {
        return $this->entity
            ->forTenant($tenantId)
            ->approved()
            ->featured()
            ->with(['order', 'user'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Obter avaliações pendentes para um tenant
     */
    public function getPendingForTenant(int $tenantId): Collection
    {
        return $this->entity
            ->forTenant($tenantId)
            ->pending()
            ->with(['order', 'user', 'moderator'])
            ->orderBy('created_at', 'asc') // Mais antigas primeiro
            ->get();
    }

    /**
     * Obter todas as avaliações de um tenant (com filtro opcional)
     */
    public function getAllForTenant(int $tenantId, ?string $status = null): Collection
    {
        $query = $this->entity
            ->forTenant($tenantId)
            ->with(['order', 'user', 'moderator']);

        if ($status && $status !== 'all') {
            switch ($status) {
                case 'pending':
                    $query->pending();
                    break;
                case 'approved':
                    $query->approved();
                    break;
                case 'rejected':
                    $query->rejected();
                    break;
            }
        }

        return $query->orderBy('created_at', 'desc')->get();
    }

    /**
     * Verificar se um pedido já possui avaliação
     */
    public function getByOrderId(int $orderId): ?Review
    {
        return $this->entity
            ->where('order_id', $orderId)
            ->first();
    }

    /**
     * Obter estatísticas de avaliações para um tenant
     */
    public function getStats(int $tenantId): array
    {
        $reviews = $this->entity->forTenant($tenantId)->approved()->get();
        
        $total = $reviews->count();
        $averageRating = $total > 0 ? round($reviews->avg('rating'), 2) : 0;
        
        $ratingDistribution = [
            '5' => $reviews->where('rating', 5)->count(),
            '4' => $reviews->where('rating', 4)->count(),
            '3' => $reviews->where('rating', 3)->count(),
            '2' => $reviews->where('rating', 2)->count(),
            '1' => $reviews->where('rating', 1)->count(),
        ];

        // Calcular percentuais
        $ratingPercentages = [];
        foreach ($ratingDistribution as $rating => $count) {
            $ratingPercentages[$rating] = $total > 0 
                ? round(($count / $total) * 100, 1) 
                : 0;
        }

        return [
            'total' => $total,
            'average_rating' => $averageRating,
            'rating_distribution' => $ratingDistribution,
            'rating_percentages' => $ratingPercentages,
            'pending_count' => $this->entity->forTenant($tenantId)->pending()->count(),
            'approved_count' => $total,
            'rejected_count' => $this->entity->forTenant($tenantId)->rejected()->count(),
            'featured_count' => $this->entity->forTenant($tenantId)->featured()->count(),
        ];
    }

    /**
     * Obter últimas avaliações (para dashboard)
     */
    public function getRecentForDashboard(int $tenantId, int $limit = 5): Collection
    {
        return $this->entity
            ->forTenant($tenantId)
            ->approved()
            ->with(['order'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Buscar avaliação por UUID
     */
    public function findByUuid(string $uuid): ?Review
    {
        return $this->entity
            ->where('uuid', $uuid)
            ->first();
    }

    /**
     * Deletar avaliação (soft delete)
     */
    public function deleteByUuid(string $uuid): bool
    {
        $review = $this->findByUuid($uuid);
        
        if (!$review) {
            return false;
        }

        return $review->delete();
    }

    /**
     * Aprovar avaliação
     */
    public function approve(string $uuid, int $moderatorId, ?string $reason = null): ?Review
    {
        $review = $this->findByUuid($uuid);
        
        if (!$review) {
            return null;
        }

        $review->approve($moderatorId, $reason);
        
        return $review->fresh(['order', 'user', 'moderator']);
    }

    /**
     * Rejeitar avaliação
     */
    public function reject(string $uuid, int $moderatorId, ?string $reason = null): ?Review
    {
        $review = $this->findByUuid($uuid);
        
        if (!$review) {
            return null;
        }

        $review->reject($moderatorId, $reason);
        
        return $review->fresh(['order', 'user', 'moderator']);
    }

    /**
     * Toggle featured status
     */
    public function toggleFeatured(string $uuid): ?Review
    {
        $review = $this->findByUuid($uuid);
        
        if (!$review) {
            return null;
        }

        if ($review->is_featured) {
            $review->unmarkAsFeatured();
        } else {
            $review->markAsFeatured();
        }

        return $review->fresh();
    }
}

