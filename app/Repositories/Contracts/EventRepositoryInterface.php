<?php

namespace App\Repositories\Contracts;

use App\Models\Event;
use Illuminate\Support\Collection;

interface EventRepositoryInterface
{
    /**
     * Get all events for a tenant
     */
    public function getAllByTenant(int $tenantId): Collection;

    /**
     * Get events between dates for a tenant
     */
    public function getByDateRange(int $tenantId, string $startDate, string $endDate): Collection;

    /**
     * Get event by UUID
     */
    public function getByUuid(string $uuid): ?Event;

    /**
     * Create new event
     */
    public function create(array $data): Event;

    /**
     * Update event
     */
    public function update(string $uuid, array $data): ?Event;

    /**
     * Delete event
     */
    public function delete(string $uuid): bool;

    /**
     * Attach clients to event
     */
    public function attachClients(int $eventId, array $clientIds): void;

    /**
     * Sync clients to event
     */
    public function syncClients(int $eventId, array $clientIds): void;

    /**
     * Mark notifications as sent for clients
     */
    public function markNotificationsSent(int $eventId, array $clientIds, string $channel): void;

    /**
     * Get upcoming events for tenant
     */
    public function getUpcoming(int $tenantId, int $limit = 10): Collection;

    /**
     * Get event statistics for tenant
     */
    public function getStats(int $tenantId): array;
}

