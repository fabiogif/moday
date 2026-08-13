<?php

namespace App\Services;

use App\Repositories\Contracts\PDVFeedbackRepositoryInterface;
use App\Models\PDVFeedback;
use Illuminate\Support\Facades\Log;

class PDVFeedbackService
{
    public function __construct(
        protected PDVFeedbackRepositoryInterface $feedbackRepository
    ) {
    }

    /**
     * Criar novo feedback
     */
    public function createFeedback(array $data, int $tenantId, ?int $userId = null): PDVFeedback
    {
        $feedbackData = [
            'tenant_id' => $tenantId,
            'user_id' => $userId,
            'type' => $data['type'] ?? 'suggestion',
            'rating' => $data['rating'] ?? null,
            'message' => $data['message'] ?? null,
            'quick_emoji' => $data['quick_emoji'] ?? null,
            'metadata' => $data['metadata'] ?? null,
            'status' => 'pending',
        ];

        try {
            return $this->feedbackRepository->createFeedback($feedbackData);
        } catch (\Exception $e) {
            Log::error('Erro ao criar feedback do PDV', [
                'error' => $e->getMessage(),
                'data' => $feedbackData,
            ]);
            throw $e;
        }
    }

    /**
     * Listar feedbacks por tenant
     */
    public function getFeedbacksByTenant(int $tenantId, ?string $type = null, ?string $status = null, int $page = 1, int $perPage = 15)
    {
        return $this->feedbackRepository->getFeedbacksByTenant($tenantId, $type, $status, $page, $perPage);
    }

    /**
     * Buscar feedback por UUID
     */
    public function getFeedbackByUuid(string $uuid, int $tenantId): ?PDVFeedback
    {
        return $this->feedbackRepository->getFeedbackByUuid($uuid, $tenantId);
    }

    /**
     * Atualizar status do feedback
     */
    public function updateFeedbackStatus(string $uuid, int $tenantId, string $status, ?int $reviewedBy = null, ?string $reviewNotes = null): bool
    {
        $validStatuses = ['pending', 'reviewed', 'resolved', 'dismissed'];
        
        if (!in_array($status, $validStatuses)) {
            throw new \InvalidArgumentException("Status inválido: {$status}");
        }

        return $this->feedbackRepository->updateFeedbackStatus($uuid, $tenantId, $status, $reviewedBy, $reviewNotes);
    }
}

