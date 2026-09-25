<?php

namespace App\Repositories\Contracts;

use App\Models\Integrations\Ifood\IfoodEvent;

interface IfoodEventRepositoryInterface
{
    public function create(array $data): IfoodEvent;

    public function find(int $id): ?IfoodEvent;

    public function findByEventId(int $tenantId, string $eventId): ?IfoodEvent;

    public function updateStatus(IfoodEvent $event, string $status, ?string $errorMessage = null): void;
}


