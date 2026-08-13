<?php

namespace App\Repositories\Contracts;

use App\Models\NotificationPreference;
use Illuminate\Database\Eloquent\Collection;

interface NotificationPreferenceRepositoryInterface
{
    /**
     * Get preferences for a user
     */
    public function getByUser(int $userId, int $tenantId): Collection;

    /**
     * Get preference for a specific event type
     */
    public function getByUserAndEvent(int $userId, int $tenantId, string $eventType): ?NotificationPreference;

    /**
     * Create or update preference
     */
    public function createOrUpdate(int $userId, int $tenantId, string $eventType, array $data): NotificationPreference;

    /**
     * Update all preferences for a user
     */
    public function updateBulk(int $userId, int $tenantId, array $preferences): bool;

    /**
     * Create default preferences for a user
     */
    public function createDefaults(int $userId, int $tenantId): void;

    /**
     * Check if user has channel enabled for event
     */
    public function isChannelEnabled(int $userId, int $tenantId, string $eventType, string $channel): bool;
}

