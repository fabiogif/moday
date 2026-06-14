<?php

namespace App\Repositories\Contracts;

use App\Models\Notification;
use Illuminate\Database\Eloquent\Collection;

interface NotificationRepositoryInterface
{
    /**
     * Get notifications for a user
     */
    public function getByUser(int $userId, int $tenantId, int $perPage = 15): PaginateRepositoryInterface;

    /**
     * Get unread notifications for a user
     */
    public function getUnreadByUser(int $userId, int $tenantId): Collection;

    /**
     * Get unread count for a user
     */
    public function getUnreadCount(int $userId, int $tenantId): int;

    /**
     * Create a notification
     */
    public function create(array $data): Notification;

    /**
     * Mark notification as read
     */
    public function markAsRead(string $uuid, int $userId, int $tenantId): bool;

    /**
     * Mark all notifications as read for a user
     */
    public function markAllAsRead(int $userId, int $tenantId): int;

    /**
     * Delete a notification by UUID
     */
    public function deleteByUuid(string $uuid, int $userId, int $tenantId): bool;

    /**
     * Get notification by UUID
     */
    public function getByUuid(string $uuid, int $tenantId): ?Notification;
}

