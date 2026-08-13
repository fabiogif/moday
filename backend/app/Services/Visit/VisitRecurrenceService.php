<?php

namespace App\Services\Visit;

use App\Exceptions\Visit\VisitConflictException;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitRecurrence;
use App\Repositories\Contracts\VisitRecurrenceRepositoryInterface;
use App\Repositories\Contracts\VisitRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class VisitRecurrenceService
{
    public const DEFAULT_GENERATION_WINDOW_DAYS = 60;
    public const MAX_GENERATION_WINDOW_DAYS = 180;

    public function __construct(
        private readonly VisitRecurrenceRepositoryInterface $recurrenceRepository,
        private readonly VisitRepositoryInterface $visitRepository,
        private readonly VisitRecurrenceDateCalculator $dateCalculator,
        private readonly VisitService $visitService,
    ) {
    }

    public function list(int $tenantId, array $filters, User $requestingUser, int $perPage = 50): LengthAwarePaginator
    {
        return $this->recurrenceRepository->listWithFilters($tenantId, $filters, $requestingUser, $perPage);
    }

    public function find(int $tenantId, string $uuid): ?VisitRecurrence
    {
        return $this->recurrenceRepository->findForTenant($tenantId, $uuid);
    }

    public function store(int $tenantId, int $creatingUserId, array $data): VisitRecurrence
    {
        return $this->recurrenceRepository->create([
            'tenant_id' => $tenantId,
            'uuid' => (string) Str::uuid(),
            'client_id' => $data['client_id'],
            'user_id' => $data['user_id'] ?? $creatingUserId,
            'frequency' => $data['frequency'],
            'interval_count' => $data['interval_count'] ?? 1,
            'days_of_week' => $data['days_of_week'] ?? null,
            'day_of_month' => $data['day_of_month'] ?? null,
            'scheduled_start_time' => $data['scheduled_start_time'],
            'scheduled_end_time' => $data['scheduled_end_time'],
            'type' => $data['type'],
            'priority' => $data['priority'] ?? 'normal',
            'starts_on' => $data['starts_on'],
            'ends_on' => $data['ends_on'] ?? null,
            'is_active' => true,
        ]);
    }

    public function update(VisitRecurrence $recurrence, array $data): VisitRecurrence
    {
        $payload = array_filter([
            'user_id' => $data['user_id'] ?? null,
            'frequency' => $data['frequency'] ?? null,
            'interval_count' => $data['interval_count'] ?? null,
            'scheduled_start_time' => $data['scheduled_start_time'] ?? null,
            'scheduled_end_time' => $data['scheduled_end_time'] ?? null,
            'type' => $data['type'] ?? null,
            'priority' => $data['priority'] ?? null,
            'ends_on' => array_key_exists('ends_on', $data) ? $data['ends_on'] : null,
        ], static fn ($value) => $value !== null);

        if (array_key_exists('days_of_week', $data)) {
            $payload['days_of_week'] = $data['days_of_week'];
        }

        if (array_key_exists('day_of_month', $data)) {
            $payload['day_of_month'] = $data['day_of_month'];
        }

        if (array_key_exists('is_active', $data)) {
            $payload['is_active'] = (bool) $data['is_active'];
        }

        return $this->recurrenceRepository->update($recurrence, $payload);
    }

    public function deactivate(VisitRecurrence $recurrence): VisitRecurrence
    {
        return $this->recurrenceRepository->update($recurrence, ['is_active' => false]);
    }

    /**
     * Materializa as próximas ocorrências da recorrência em registros Visit reais.
     * Não bloqueia em conflito de horário — pula a data e reporta em "skipped",
     * já que a geração roda em lote e um único conflito não deve travar as demais.
     *
     * @return array{created: Visit[], skipped: array<array{date: string, reason: string}>}
     */
    public function generateOccurrences(VisitRecurrence $recurrence, int $windowDays, int $creatingUserId): array
    {
        $windowDays = min(max($windowDays, 1), self::MAX_GENERATION_WINDOW_DAYS);

        $windowStart = Carbon::today();
        $windowEnd = $windowStart->copy()->addDays($windowDays);

        $occurrenceDates = $this->dateCalculator->occurrencesWithin($recurrence, $windowStart, $windowEnd);
        $alreadyGenerated = $this->visitRepository->scheduledDatesForRecurrence((int) $recurrence->tenant_id, $recurrence->id);

        $created = [];
        $skipped = [];

        foreach ($occurrenceDates as $date) {
            if (in_array($date, $alreadyGenerated, true)) {
                continue;
            }

            try {
                $result = $this->visitService->store((int) $recurrence->tenant_id, $creatingUserId, [
                    'user_id' => $recurrence->user_id,
                    'client_id' => $recurrence->client_id,
                    'scheduled_date' => $date,
                    'scheduled_start_time' => $recurrence->scheduled_start_time,
                    'scheduled_end_time' => $recurrence->scheduled_end_time,
                    'type' => $recurrence->type,
                    'priority' => $recurrence->priority,
                    'recurrence_id' => $recurrence->id,
                ]);

                $created[] = $result['visit'];
            } catch (VisitConflictException $ex) {
                $skipped[] = ['date' => $date, 'reason' => $ex->getMessage()];
            }
        }

        return ['created' => $created, 'skipped' => $skipped];
    }
}
