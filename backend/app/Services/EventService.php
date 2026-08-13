<?php

namespace App\Services;

use App\Models\Event;
use App\Repositories\Contracts\EventRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EventService
{
    public function __construct(
        private readonly EventRepositoryInterface $eventRepository,
        private readonly EventNotificationService $notificationService
    ) {}

    /**
     * Get all events for tenant
     */
    public function getAllByTenant(int $tenantId): Collection
    {
        return $this->eventRepository->getAllByTenant($tenantId);
    }

    /**
     * Get events by date range
     */
    public function getByDateRange(int $tenantId, string $startDate, string $endDate): Collection
    {
        return $this->eventRepository->getByDateRange($tenantId, $startDate, $endDate);
    }

    /**
     * Get event by UUID
     */
    public function getByUuid(string $uuid, int $tenantId): ?Event
    {
        $event = $this->eventRepository->getByUuid($uuid);
        
        // Verificar se pertence ao tenant
        if ($event && $event->tenant_id !== $tenantId) {
            return null;
        }

        return $event;
    }

    /**
     * Create new event with clients
     */
    public function create(array $data, int $tenantId): Event
    {
        return DB::transaction(function () use ($data, $tenantId) {
            $clientIds = $data['client_ids'] ?? [];
            $notificationChannels = $data['notification_channels'] ?? [];
            
            unset($data['client_ids'], $data['notification_channels']);

            // Criar evento
            $eventData = array_merge($data, ['tenant_id' => $tenantId]);
            $event = $this->eventRepository->create($eventData);

            // Vincular clientes
            if (!empty($clientIds)) {
                $this->eventRepository->syncClients($event->id, $clientIds);
            }

            // Enviar notificações se solicitado
            if (!empty($notificationChannels) && !empty($clientIds)) {
                $this->sendEventNotifications($event, $notificationChannels);
            }

            return $event->load('clients');
        });
    }

    /**
     * Update existing event
     */
    public function update(string $uuid, array $data, int $tenantId): ?Event
    {
        return DB::transaction(function () use ($uuid, $data, $tenantId) {
            $event = $this->getByUuid($uuid, $tenantId);
            
            if (!$event) {
                return null;
            }

            $clientIds = $data['client_ids'] ?? null;
            $notificationChannels = $data['notification_channels'] ?? [];
            
            unset($data['client_ids'], $data['notification_channels']);

            // Atualizar evento
            $updatedEvent = $this->eventRepository->update($uuid, $data);

            // Sincronizar clientes se fornecido
            if ($clientIds !== null) {
                $this->eventRepository->syncClients($updatedEvent->id, $clientIds);
            }

            // Enviar notificações se solicitado
            if (!empty($notificationChannels) && !empty($clientIds)) {
                $this->sendEventNotifications($updatedEvent->fresh(['clients']), $notificationChannels);
            }

            return $updatedEvent->fresh(['clients']);
        });
    }

    /**
     * Delete event
     */
    public function delete(string $uuid, int $tenantId): bool
    {
        $event = $this->getByUuid($uuid, $tenantId);
        
        if (!$event) {
            return false;
        }

        // Auditoria antes de deletar
        Log::info('Evento sendo deletado', [
            'event_id' => $event->id,
            'event_uuid' => $uuid,
            'tenant_id' => $tenantId,
            'user_id' => auth()->id(),
            'user_email' => auth()->user()?->email,
            'event_title' => $event->title,
            'event_type' => $event->type,
            'event_start_date' => $event->start_date?->toIso8601String(),
            'deleted_at' => now()->toIso8601String(),
            'request_id' => request()->header('X-Request-ID'),
        ]);

        return $this->eventRepository->delete($uuid);
    }

    /**
     * Get upcoming events
     */
    public function getUpcoming(int $tenantId, int $limit = 10): Collection
    {
        return $this->eventRepository->getUpcoming($tenantId, $limit);
    }

    /**
     * Get event statistics
     */
    public function getStats(int $tenantId): array
    {
        return $this->eventRepository->getStats($tenantId);
    }

    /**
     * Send notifications for event
     */
    private function sendEventNotifications(Event $event, array $channels): void
    {
        try {
            $results = $this->notificationService->sendNotifications(
                $event,
                $event->clients,
                $channels
            );

            // Marcar notificações como enviadas
            foreach ($channels as $channel) {
                if ($results[$channel]['sent'] > 0) {
                    $clientIds = $event->clients->pluck('id')->toArray();
                    $this->eventRepository->markNotificationsSent($event->id, $clientIds, $channel);
                }
            }

            // Atualizar flag do evento
            if (array_sum(array_column($results, 'sent')) > 0) {
                $event->update(['notifications_sent' => true]);
            }

            Log::info('Notificações de evento enviadas', [
                'event_id' => $event->id,
                'results' => $results,
            ]);
        } catch (\Exception $e) {
            Log::error('Erro ao enviar notificações de evento: ' . $e->getMessage(), [
                'event_id' => $event->id,
                'error' => $e->getMessage(),
            ]);
        }
    }
}

