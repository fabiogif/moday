<?php

namespace App\Services\Integrations\Ifood;

use App\Models\Integrations\Ifood\IfoodEvent;
use App\Repositories\Contracts\IfoodEventRepositoryInterface;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class IfoodEventService
{
    public function __construct(
        private readonly IfoodEventRepositoryInterface $repository
    ) {
    }

    public function recordEvent(int $tenantId, array $payload): IfoodEvent
    {
        $eventId = $this->extractEventId($payload);

        if ($eventId) {
            $existing = $this->repository->findByEventId($tenantId, $eventId);

            if ($existing) {
                return $existing;
            }
        } else {
            $eventId = (string) Str::uuid();
        }

        return $this->repository->create([
            'tenant_id' => $tenantId,
            'event_id' => $eventId,
            'event_type' => $this->extractEventType($payload),
            'status' => 'pending',
            'payload' => $payload,
            'received_at' => now(),
        ]);
    }

    public function find(int $eventId): ?IfoodEvent
    {
        return $this->repository->find($eventId);
    }

    public function markProcessed(IfoodEvent $event): void
    {
        $this->repository->updateStatus($event, 'processed');
    }

    public function markFailed(IfoodEvent $event, string $message): void
    {
        $this->repository->updateStatus($event, 'failed', $message);
    }

    public function markSkipped(IfoodEvent $event, ?string $message = null): void
    {
        $this->repository->updateStatus($event, 'skipped', $message);
    }

    private function extractEventId(array $payload): ?string
    {
        return Arr::get($payload, 'id')
            ?? Arr::get($payload, 'eventId')
            ?? Arr::get($payload, 'event_id')
            ?? Arr::get($payload, 'order.id');
    }

    private function extractEventType(array $payload): ?string
    {
        return Arr::get($payload, 'code')
            ?? Arr::get($payload, 'eventName')
            ?? Arr::get($payload, 'eventType')
            ?? Arr::get($payload, 'type')
            ?? Arr::get($payload, 'order.type');
    }
}


