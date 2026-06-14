<?php

namespace App\Repositories;

use App\Models\Integrations\Ifood\IfoodEvent;
use App\Repositories\Contracts\IfoodEventRepositoryInterface;

class IfoodEventRepository implements IfoodEventRepositoryInterface
{
    public function __construct(
        private readonly IfoodEvent $model = new IfoodEvent()
    ) {
    }

    public function create(array $data): IfoodEvent
    {
        return $this->model->newQuery()->create($data);
    }

    public function find(int $id): ?IfoodEvent
    {
        return $this->model->newQuery()->find($id);
    }

    public function findByEventId(int $tenantId, string $eventId): ?IfoodEvent
    {
        return $this->model->newQuery()
            ->where('tenant_id', $tenantId)
            ->where('event_id', $eventId)
            ->latest()
            ->first();
    }

    public function updateStatus(IfoodEvent $event, string $status, ?string $errorMessage = null): void
    {
        $event->fill([
            'status' => $status,
            'processed_at' => in_array($status, ['processed', 'skipped'], true) ? now() : null,
            'error_message' => $errorMessage,
        ]);

        $event->save();
    }
}


