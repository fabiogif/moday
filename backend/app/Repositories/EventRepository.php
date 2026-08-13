<?php

namespace App\Repositories;

use App\Models\Event;
use App\Repositories\Contracts\EventRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class EventRepository implements EventRepositoryInterface
{
    public function __construct(private readonly Event $model)
    {
    }

    public function getAllByTenant(int $tenantId): Collection
    {
        return $this->model
            ->where('tenant_id', $tenantId)
            ->with('clients')
            ->orderBy('start_date', 'desc')
            ->get();
    }

    public function getByDateRange(int $tenantId, string $startDate, string $endDate): Collection
    {
        return $this->model
            ->where('tenant_id', $tenantId)
            ->whereBetween('start_date', [$startDate, $endDate])
            ->with('clients')
            ->orderBy('start_date', 'asc')
            ->get();
    }

    public function getByUuid(string $uuid): ?Event
    {
        return $this->model
            ->where('uuid', $uuid)
            ->with('clients')
            ->first();
    }

    public function create(array $data): Event
    {
        return $this->model->create($data);
    }

    public function update(string $uuid, array $data): ?Event
    {
        $event = $this->getByUuid($uuid);
        
        if (!$event) {
            return null;
        }

        $event->update($data);
        return $event->fresh(['clients']);
    }

    public function delete(string $uuid): bool
    {
        $event = $this->model->where('uuid', $uuid)->first();
        
        if (!$event) {
            return false;
        }

        return $event->delete();
    }

    public function attachClients(int $eventId, array $clientIds): void
    {
        $event = $this->model->find($eventId);
        
        if ($event) {
            $event->clients()->attach($clientIds);
        }
    }

    public function syncClients(int $eventId, array $clientIds): void
    {
        $event = $this->model->find($eventId);
        
        if ($event) {
            $event->clients()->sync($clientIds);
        }
    }

    public function markNotificationsSent(int $eventId, array $clientIds, string $channel): void
    {
        $columnMap = [
            'email' => 'email_sent',
            'whatsapp' => 'whatsapp_sent',
            'sms' => 'sms_sent',
        ];

        $column = $columnMap[$channel] ?? null;
        
        if (!$column) {
            return;
        }

        DB::table('event_clients')
            ->where('event_id', $eventId)
            ->whereIn('client_id', $clientIds)
            ->update([
                $column => true,
                'notified_at' => now(),
            ]);
    }

    public function getUpcoming(int $tenantId, int $limit = 10): Collection
    {
        return $this->model
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->where('start_date', '>=', now())
            ->orderBy('start_date', 'asc')
            ->limit($limit)
            ->get();
    }

    public function getStats(int $tenantId): array
    {
        $total = $this->model->where('tenant_id', $tenantId)->count();
        $active = $this->model->where('tenant_id', $tenantId)->where('is_active', true)->count();
        $upcoming = $this->model
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->where('start_date', '>=', now())
            ->count();
        $past = $this->model
            ->where('tenant_id', $tenantId)
            ->where('start_date', '<', now())
            ->count();
        $notificationsSent = $this->model
            ->where('tenant_id', $tenantId)
            ->where('notifications_sent', true)
            ->count();

        return [
            'total' => $total,
            'active' => $active,
            'upcoming' => $upcoming,
            'past' => $past,
            'notifications_sent' => $notificationsSent,
        ];
    }
}

