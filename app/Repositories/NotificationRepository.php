<?php

namespace App\Repositories;

use App\Models\Notification;
use App\Repositories\Contracts\NotificationRepositoryInterface;
use App\Repositories\Contracts\PaginateRepositoryInterface;
use App\Repositories\Contracts\Presenter\PaginatePresenter;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class NotificationRepository extends BaseRepository implements NotificationRepositoryInterface
{
    public function __construct(protected Model $entity = new Notification())
    {
    }

    /**
     * Get notifications for a user
     */
    public function getByUser(int $userId, int $tenantId, int $perPage = 15): PaginateRepositoryInterface
    {
        $result = $this->entity
            ->where('user_id', $userId)
            ->where('tenant_id', $tenantId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return new PaginatePresenter($result);
    }

    /**
     * Get unread notifications for a user
     */
    public function getUnreadByUser(int $userId, int $tenantId): Collection
    {
        return $this->entity
            ->where('user_id', $userId)
            ->where('tenant_id', $tenantId)
            ->whereNull('read_at')
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Get unread count for a user
     */
    public function getUnreadCount(int $userId, int $tenantId): int
    {
        return $this->entity
            ->where('user_id', $userId)
            ->where('tenant_id', $tenantId)
            ->whereNull('read_at')
            ->count();
    }

    /**
     * Create a notification
     */
    public function create(array $data): Notification
    {
        return $this->entity->create($data);
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(string $uuid, int $userId, int $tenantId): bool
    {
        $notification = $this->entity
            ->where('uuid', $uuid)
            ->where('user_id', $userId)
            ->where('tenant_id', $tenantId)
            ->first();

        if (!$notification) {
            return false;
        }

        return $notification->markAsRead();
    }

    /**
     * Mark all notifications as read for a user
     */
    public function markAllAsRead(int $userId, int $tenantId): int
    {
        return $this->entity
            ->where('user_id', $userId)
            ->where('tenant_id', $tenantId)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    /**
     * Delete a notification by UUID (override parent method)
     */
    public function deleteByUuid(string $uuid, int $userId, int $tenantId): bool
    {
        $notification = $this->entity
            ->where('uuid', $uuid)
            ->where('user_id', $userId)
            ->where('tenant_id', $tenantId)
            ->first();

        if (!$notification) {
            return false;
        }

        return $notification->delete();
    }

    /**
     * Get notification by UUID
     */
    public function getByUuid(string $uuid, int $tenantId): ?Notification
    {
        return $this->entity
            ->where('uuid', $uuid)
            ->where('tenant_id', $tenantId)
            ->first();
    }
}

