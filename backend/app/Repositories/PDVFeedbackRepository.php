<?php

namespace App\Repositories;

use App\Models\PDVFeedback;
use App\Repositories\Contracts\PDVFeedbackRepositoryInterface;
use App\Repositories\Contracts\PaginateRepositoryInterface;
use App\Repositories\Contracts\Presenter\PaginatePresenter;
use Illuminate\Database\Eloquent\Model;

class PDVFeedbackRepository extends BaseRepository implements PDVFeedbackRepositoryInterface
{
    public function __construct(protected Model $entity = new PDVFeedback())
    {
    }

    public function createFeedback(array $data): PDVFeedback
    {
        return $this->entity->create($data);
    }

    public function getFeedbacksByTenant(int $tenantId, ?string $type = null, ?string $status = null, int $page = 1, int $perPage = 15): PaginateRepositoryInterface
    {
        $query = $this->entity->with(['user', 'reviewer'])
            ->where('tenant_id', $tenantId);

        if ($type) {
            $query->where('type', $type);
        }

        if ($status) {
            $query->where('status', $status);
        }

        $result = $query->orderByDesc('created_at')
            ->paginate(perPage: $perPage, columns: ['*'], pageName: 'page', page: $page, total: null);

        return new PaginatePresenter($result);
    }

    public function getFeedbackByUuid(string $uuid, int $tenantId): ?PDVFeedback
    {
        return $this->entity->with(['user', 'reviewer'])
            ->where('uuid', $uuid)
            ->where('tenant_id', $tenantId)
            ->first();
    }

    public function updateFeedbackStatus(string $uuid, int $tenantId, string $status, ?int $reviewedBy = null, ?string $reviewNotes = null): bool
    {
        $feedback = $this->entity->where('uuid', $uuid)
            ->where('tenant_id', $tenantId)
            ->first();

        if (!$feedback) {
            return false;
        }

        $updateData = [
            'status' => $status,
        ];

        if ($reviewedBy) {
            $updateData['reviewed_by'] = $reviewedBy;
            $updateData['reviewed_at'] = now();
        }

        if ($reviewNotes) {
            $updateData['review_notes'] = $reviewNotes;
        }

        return $feedback->update($updateData);
    }
}

