<?php

namespace App\Repositories;

use App\Models\NotificationPreference;
use App\Repositories\Contracts\NotificationPreferenceRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class NotificationPreferenceRepository implements NotificationPreferenceRepositoryInterface
{
    public function __construct(
        protected Model $entity = new NotificationPreference()
    ) {}

    /**
     * Get preferences for a user
     */
    public function getByUser(int $userId, int $tenantId): Collection
    {
        return $this->entity
            ->where('user_id', $userId)
            ->where('tenant_id', $tenantId)
            ->get();
    }

    /**
     * Get preference for a specific event type
     */
    public function getByUserAndEvent(int $userId, int $tenantId, string $eventType): ?NotificationPreference
    {
        return $this->entity
            ->where('user_id', $userId)
            ->where('tenant_id', $tenantId)
            ->where('event_type', $eventType)
            ->first();
    }

    /**
     * Create or update preference
     */
    public function createOrUpdate(int $userId, int $tenantId, string $eventType, array $data): NotificationPreference
    {
        return $this->entity->updateOrCreate(
            [
                'user_id' => $userId,
                'tenant_id' => $tenantId,
                'event_type' => $eventType,
            ],
            array_merge($data, [
                'uuid' => $data['uuid'] ?? Str::uuid(),
            ])
        );
    }

    /**
     * Update all preferences for a user
     */
    public function updateBulk(int $userId, int $tenantId, array $preferences): bool
    {
        try {
            foreach ($preferences as $preference) {
                // Se é array numérico com objetos
                if (isset($preference['event_type'])) {
                    $eventType = $preference['event_type'];
                    $settings = $preference;
                    unset($settings['event_type']); // Remover event_type dos settings
                } else {
                    // Se é array associativo (chave = event_type)
                    $eventType = key($preference);
                    $settings = current($preference);
                }
                
                $this->createOrUpdate($userId, $tenantId, $eventType, $settings);
            }
            return true;
        } catch (\Exception $e) {
            \Log::error('NotificationPreferenceRepository::updateBulk - Error:', [
                'error' => $e->getMessage(),
                'preferences' => $preferences
            ]);
            return false;
        }
    }

    /**
     * Create default preferences for a user
     */
    public function createDefaults(int $userId, int $tenantId): void
    {
        $defaults = [
            'user_created' => ['email_enabled' => true, 'push_enabled' => false],
            'order_created' => ['email_enabled' => true, 'push_enabled' => true],
            'order_status_changed' => ['email_enabled' => true, 'push_enabled' => true],
            'order_completed' => ['email_enabled' => false, 'push_enabled' => true],
            'order_cancelled' => ['email_enabled' => true, 'push_enabled' => true],
            'product_stock_low' => ['email_enabled' => true, 'push_enabled' => true],
            'product_out_of_stock' => ['email_enabled' => true, 'push_enabled' => true],
            'client_created' => ['email_enabled' => false, 'push_enabled' => true],
            'client_first_purchase' => ['email_enabled' => true, 'push_enabled' => true],
        ];

        foreach ($defaults as $eventType => $settings) {
            $this->createOrUpdate($userId, $tenantId, $eventType, $settings);
        }
    }

    /**
     * Check if user has channel enabled for event
     */
    public function isChannelEnabled(int $userId, int $tenantId, string $eventType, string $channel): bool
    {
        $preference = $this->getByUserAndEvent($userId, $tenantId, $eventType);

        if (!$preference) {
            // Se não tem preferência, usar padrão (habilitado)
            return true;
        }

        return $preference->isChannelEnabled($channel);
    }
}

