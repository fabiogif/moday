<?php

namespace App\Services;

use App\Repositories\Contracts\NotificationRepositoryInterface;
use App\Repositories\Contracts\NotificationPreferenceRepositoryInterface;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

readonly class NotificationService
{
    public function __construct(
        private NotificationRepositoryInterface $notificationRepository,
        private NotificationPreferenceRepositoryInterface $preferenceRepository
    ) {}

    /**
     * Get notifications for a user
     */
    public function getUserNotifications(int $userId, int $tenantId, int $perPage = 15)
    {
        return $this->notificationRepository->getByUser($userId, $tenantId, $perPage);
    }

    /**
     * Get unread notifications for a user
     */
    public function getUnreadNotifications(int $userId, int $tenantId)
    {
        return $this->notificationRepository->getUnreadByUser($userId, $tenantId);
    }

    /**
     * Get unread count for a user
     */
    public function getUnreadCount(int $userId, int $tenantId): int
    {
        return $this->notificationRepository->getUnreadCount($userId, $tenantId);
    }

    /**
     * Create and send a notification
     */
    public function send(int $userId, int $tenantId, string $type, array $data): bool
    {
        try {
            // Verificar preferências do usuário
            $preference = $this->preferenceRepository->getByUserAndEvent($userId, $tenantId, $type);

            // Se não tem preferência, usar padrões
            $emailEnabled = $preference ? $preference->email_enabled : true;
            $pushEnabled = $preference ? $preference->push_enabled : true;

            $sent = false;

            // Enviar por email se habilitado
            if ($emailEnabled) {
                $sent = $this->sendEmail($userId, $tenantId, $type, $data) || $sent;
            }

            // Enviar push se habilitado
            if ($pushEnabled) {
                $sent = $this->sendPush($userId, $tenantId, $type, $data) || $sent;
            }

            return $sent;
        } catch (\Exception $e) {
            Log::error('NotificationService::send - Error:', [
                'user_id' => $userId,
                'tenant_id' => $tenantId,
                'type' => $type,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Send email notification
     */
    private function sendEmail(int $userId, int $tenantId, string $type, array $data): bool
    {
        try {
            $notification = $this->notificationRepository->create([
                'uuid' => Str::uuid(),
                'tenant_id' => $tenantId,
                'user_id' => $userId,
                'type' => $type,
                'channel' => 'email',
                'title' => $data['title'] ?? 'Notificação',
                'message' => $data['message'] ?? '',
                'data' => $data,
                'notifiable_type' => $data['notifiable_type'] ?? null,
                'notifiable_id' => $data['notifiable_id'] ?? null,
                'sent_at' => now(),
                'is_success' => true,
            ]);

            // Aqui você pode adicionar lógica real de envio de email
            // Mail::to($user->email)->send(new NotificationMail($notification));

            return true;
        } catch (\Exception $e) {
            Log::error('NotificationService::sendEmail - Error:', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Send push notification
     */
    private function sendPush(int $userId, int $tenantId, string $type, array $data): bool
    {
        try {
            $notification = $this->notificationRepository->create([
                'uuid' => Str::uuid(),
                'tenant_id' => $tenantId,
                'user_id' => $userId,
                'type' => $type,
                'channel' => 'push',
                'title' => $data['title'] ?? 'Notificação',
                'message' => $data['message'] ?? '',
                'data' => $data,
                'notifiable_type' => $data['notifiable_type'] ?? null,
                'notifiable_id' => $data['notifiable_id'] ?? null,
                'sent_at' => now(),
                'is_success' => true,
            ]);

            // Aqui você pode adicionar lógica real de push notification
            // via Laravel WebPush ou Firebase

            return true;
        } catch (\Exception $e) {
            Log::error('NotificationService::sendPush - Error:', [
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(string $uuid, int $userId, int $tenantId): bool
    {
        return $this->notificationRepository->markAsRead($uuid, $userId, $tenantId);
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead(int $userId, int $tenantId): int
    {
        return $this->notificationRepository->markAllAsRead($userId, $tenantId);
    }

    /**
     * Delete notification
     */
    public function deleteNotification(string $uuid, int $userId, int $tenantId): bool
    {
        return $this->notificationRepository->deleteByUuid($uuid, $userId, $tenantId);
    }

    /**
     * Get user preferences
     */
    public function getUserPreferences(int $userId, int $tenantId)
    {
        return $this->preferenceRepository->getByUser($userId, $tenantId);
    }

    /**
     * Update user preferences
     */
    public function updatePreferences(int $userId, int $tenantId, array $preferences): bool
    {
        return $this->preferenceRepository->updateBulk($userId, $tenantId, $preferences);
    }

    /**
     * Create default preferences for a new user
     */
    public function createDefaultPreferences(int $userId, int $tenantId): void
    {
        $this->preferenceRepository->createDefaults($userId, $tenantId);
    }
}

