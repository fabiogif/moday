<?php

namespace App\Services\Visit;

use App\Events\VisitCancelled;
use App\Events\VisitRescheduled;
use App\Events\VisitScheduled;
use App\Exceptions\Visit\VisitConflictException;
use App\Exceptions\Visit\VisitOutOfBusinessHoursException;
use App\Models\Client;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitStatusHistory;
use App\Repositories\Contracts\VisitRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class VisitService
{
    public function __construct(
        private readonly VisitRepositoryInterface $visitRepository,
        private readonly VisitStatusMachine $statusMachine,
        private readonly VisitAuditService $auditService,
    ) {
    }

    public function list(int $tenantId, array $filters, User $requestingUser, int $perPage = 50): LengthAwarePaginator
    {
        return $this->visitRepository->listWithFilters($tenantId, $filters, $requestingUser, $perPage);
    }

    public function find(int $tenantId, string $uuid, array $with = ['client', 'user', 'statusHistories', 'media', 'presentedProducts']): ?Visit
    {
        return $this->visitRepository->findForTenant($tenantId, $uuid, $with);
    }

    /**
     * @return array{visit: Visit, warnings: string[]}
     */
    public function store(int $tenantId, int $creatingUserId, array $data, bool $force = false): array
    {
        return DB::transaction(function () use ($tenantId, $creatingUserId, $data, $force) {
            $sellerId = (int) ($data['user_id'] ?? $creatingUserId);
            $warnings = [];

            if (!empty($data['client_request_id'])) {
                $duplicate = $this->visitRepository->findByClientRequestId($tenantId, $data['client_request_id']);
                if ($duplicate) {
                    return ['visit' => $duplicate->fresh(['client', 'user']), 'warnings' => []];
                }
            }

            if (!$force) {
                $this->assertNoConflict($tenantId, $sellerId, $data['scheduled_date'], $data['scheduled_start_time'], $data['scheduled_end_time']);
            }

            $client = Client::query()->where('tenant_id', $tenantId)->find($data['client_id']);
            $businessHoursWarning = $client ? $this->checkBusinessHours($client, $data['scheduled_start_time'], $data['scheduled_end_time']) : null;
            if ($businessHoursWarning) {
                $warnings[] = $businessHoursWarning;
            }

            $visit = $this->visitRepository->create([
                'tenant_id' => $tenantId,
                'uuid' => (string) Str::uuid(),
                'user_id' => $sellerId,
                'client_id' => $data['client_id'],
                'created_by_user_id' => $creatingUserId,
                'scheduled_date' => $data['scheduled_date'],
                'scheduled_start_time' => $data['scheduled_start_time'],
                'scheduled_end_time' => $data['scheduled_end_time'],
                'type' => $data['type'],
                'priority' => $data['priority'] ?? 'normal',
                'status' => 'agendada',
                'objective_notes' => $data['objective_notes'] ?? null,
                'recurrence_id' => $data['recurrence_id'] ?? null,
                'client_request_id' => $data['client_request_id'] ?? null,
                'notes' => $data['notes'] ?? null,
            ]);

            $this->recordStatusHistory($visit, null, 'agendada', $creatingUserId, null);
            $this->auditService->log($visit, 'created', $creatingUserId, null, $visit->toArray());

            event(new VisitScheduled($visit));

            return ['visit' => $visit->fresh(['client', 'user']), 'warnings' => $warnings];
        });
    }

    /**
     * @return array{visit: Visit, warnings: string[]}
     */
    public function update(Visit $visit, array $data, int $actingUserId, bool $force = false): array
    {
        return DB::transaction(function () use ($visit, $data, $actingUserId, $force) {
            $tenantId = (int) $visit->tenant_id;
            $sellerId = (int) ($data['user_id'] ?? $visit->user_id);
            $newDate = $data['scheduled_date'] ?? $visit->scheduled_date->format('Y-m-d');
            $newStart = $data['scheduled_start_time'] ?? $visit->scheduled_start_time;
            $newEnd = $data['scheduled_end_time'] ?? $visit->scheduled_end_time;
            $warnings = [];

            $scheduleChanged = $newDate !== $visit->scheduled_date->format('Y-m-d')
                || $newStart !== $visit->scheduled_start_time
                || $newEnd !== $visit->scheduled_end_time
                || $sellerId !== (int) $visit->user_id;

            if ($scheduleChanged && !$force) {
                $this->assertNoConflict($tenantId, $sellerId, $newDate, $newStart, $newEnd, $visit->id);
            }

            $oldValues = $visit->toArray();

            $clientId = $data['client_id'] ?? $visit->client_id;
            $client = Client::query()->where('tenant_id', $tenantId)->find($clientId);
            $businessHoursWarning = $client ? $this->checkBusinessHours($client, $newStart, $newEnd) : null;
            if ($businessHoursWarning) {
                $warnings[] = $businessHoursWarning;
            }

            $payload = array_filter([
                'client_id' => $data['client_id'] ?? null,
                'user_id' => $sellerId,
                'scheduled_date' => $newDate,
                'scheduled_start_time' => $newStart,
                'scheduled_end_time' => $newEnd,
                'type' => $data['type'] ?? null,
                'priority' => $data['priority'] ?? null,
                'objective_notes' => array_key_exists('objective_notes', $data) ? $data['objective_notes'] : null,
                'notes' => array_key_exists('notes', $data) ? $data['notes'] : null,
            ], static fn ($value) => $value !== null);

            $updated = $this->visitRepository->update($visit, $payload);
            $this->auditService->log($updated, 'updated', $actingUserId, $oldValues, $updated->toArray());

            return ['visit' => $updated->fresh(['client', 'user']), 'warnings' => $warnings];
        });
    }

    public function destroy(Visit $visit, int $actingUserId): void
    {
        DB::transaction(function () use ($visit, $actingUserId) {
            $this->auditService->log($visit, 'deleted', $actingUserId, $visit->toArray(), null);
            $this->visitRepository->delete($visit);
        });
    }

    /**
     * @param  array{scheduled_date?: string, scheduled_start_time?: string, scheduled_end_time?: string, user_id?: int, force?: bool}|null  $rescheduleData
     */
    public function changeStatus(Visit $visit, string $toStatus, ?string $reason, int $actingUserId, ?array $rescheduleData = null): Visit
    {
        return DB::transaction(function () use ($visit, $toStatus, $reason, $actingUserId, $rescheduleData) {
            $this->statusMachine->assertCan($visit->status, $toStatus);

            if ($toStatus === 'reagendada') {
                return $this->handleReschedule($visit, $rescheduleData, $reason, $actingUserId);
            }

            $fromStatus = $visit->status;
            $updated = $this->visitRepository->update($visit, ['status' => $toStatus]);
            $this->recordStatusHistory($updated, $fromStatus, $toStatus, $actingUserId, $reason);
            $this->auditService->log($updated, 'status_changed', $actingUserId, ['status' => $fromStatus], ['status' => $toStatus, 'reason' => $reason]);

            if ($toStatus === 'cancelada') {
                event(new VisitCancelled($updated));
            }

            return $updated->fresh(['client', 'user']);
        });
    }

    private function handleReschedule(Visit $original, ?array $rescheduleData, ?string $reason, int $actingUserId): Visit
    {
        if (empty($rescheduleData['scheduled_date']) || empty($rescheduleData['scheduled_start_time']) || empty($rescheduleData['scheduled_end_time'])) {
            throw new \InvalidArgumentException('Dados de reagendamento (nova data e horário) são obrigatórios.');
        }

        $sellerId = (int) ($rescheduleData['user_id'] ?? $original->user_id);
        $force = (bool) ($rescheduleData['force'] ?? false);

        if (!$force) {
            $this->assertNoConflict(
                (int) $original->tenant_id,
                $sellerId,
                $rescheduleData['scheduled_date'],
                $rescheduleData['scheduled_start_time'],
                $rescheduleData['scheduled_end_time']
            );
        }

        $newVisit = $this->visitRepository->create([
            'tenant_id' => $original->tenant_id,
            'uuid' => (string) Str::uuid(),
            'user_id' => $sellerId,
            'client_id' => $original->client_id,
            'created_by_user_id' => $actingUserId,
            'scheduled_date' => $rescheduleData['scheduled_date'],
            'scheduled_start_time' => $rescheduleData['scheduled_start_time'],
            'scheduled_end_time' => $rescheduleData['scheduled_end_time'],
            'type' => $original->type,
            'priority' => $original->priority,
            'status' => 'agendada',
            'objective_notes' => $original->objective_notes,
            'rescheduled_from_visit_id' => $original->id,
            'notes' => $original->notes,
        ]);
        $this->recordStatusHistory($newVisit, null, 'agendada', $actingUserId, 'Reagendada a partir da visita anterior');
        $this->auditService->log($newVisit, 'created', $actingUserId, null, $newVisit->toArray());

        $fromStatus = $original->status;
        $updatedOriginal = $this->visitRepository->update($original, ['status' => 'reagendada']);
        $this->recordStatusHistory($updatedOriginal, $fromStatus, 'reagendada', $actingUserId, $reason);
        $this->auditService->log($updatedOriginal, 'status_changed', $actingUserId, ['status' => $fromStatus], ['status' => 'reagendada', 'reason' => $reason]);

        event(new VisitRescheduled($newVisit->fresh(['client', 'user']), $updatedOriginal));

        return $newVisit->fresh(['client', 'user']);
    }

    /**
     * @throws VisitConflictException
     */
    private function assertNoConflict(int $tenantId, int $userId, string $date, string $start, string $end, ?int $excludeVisitId = null): void
    {
        $newStartAt = Carbon::parse($date . ' ' . $start);
        $newEndAt = Carbon::parse($date . ' ' . $end);

        $existingVisits = $this->visitRepository->lockOverlappingForUser($tenantId, $userId, $date, $excludeVisitId);

        foreach ($existingVisits as $candidate) {
            $candidateDate = $candidate->scheduled_date->format('Y-m-d');
            $existingStart = Carbon::parse($candidateDate . ' ' . $candidate->scheduled_start_time);
            $existingEnd = Carbon::parse($candidateDate . ' ' . $candidate->scheduled_end_time);

            if ($newStartAt->lt($existingEnd) && $newEndAt->gt($existingStart)) {
                throw new VisitConflictException($candidate);
            }
        }
    }

    /**
     * Alerta "suave": nunca bloqueia o agendamento, só retorna uma mensagem de aviso.
     */
    private function checkBusinessHours(Client $client, string $start, string $end): ?string
    {
        if (!$client->business_hours_start || !$client->business_hours_end) {
            return null;
        }

        try {
            $start = Carbon::parse($start)->format('H:i');
            $end = Carbon::parse($end)->format('H:i');
            $businessStart = Carbon::parse($client->business_hours_start)->format('H:i');
            $businessEnd = Carbon::parse($client->business_hours_end)->format('H:i');
        } catch (\Throwable) {
            return null;
        }

        if ($start < $businessStart || $end > $businessEnd) {
            return (new VisitOutOfBusinessHoursException($businessStart, $businessEnd))->getMessage();
        }

        return null;
    }

    private function recordStatusHistory(Visit $visit, ?string $from, string $to, ?int $userId, ?string $reason): void
    {
        VisitStatusHistory::create([
            'tenant_id' => $visit->tenant_id,
            'visit_id' => $visit->id,
            'from_status' => $from,
            'to_status' => $to,
            'changed_by_user_id' => $userId,
            'reason' => $reason,
            'occurred_at' => now(),
        ]);
    }
}
