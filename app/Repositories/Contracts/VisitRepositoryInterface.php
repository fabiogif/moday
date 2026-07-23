<?php

namespace App\Repositories\Contracts;

use App\Models\User;
use App\Models\Visit;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

interface VisitRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $filters
     */
    public function listWithFilters(int $tenantId, array $filters, User $requestingUser, int $perPage = 50): LengthAwarePaginator;

    public function findForTenant(int $tenantId, string $uuid, array $with = []): ?Visit;

    public function findByClientRequestId(int $tenantId, string $clientRequestId): ?Visit;

    public function create(array $data): Visit;

    public function update(Visit $visit, array $data): Visit;

    public function delete(Visit $visit): void;

    /**
     * Visitas não-terminais do vendedor no dia, para checagem de sobreposição de horário.
     * Deve ser chamado dentro de uma transação — aplica lock pessimista.
     */
    public function lockOverlappingForUser(int $tenantId, int $userId, string $date, ?int $excludeVisitId = null): Collection;

    /**
     * Datas (Y-m-d) já materializadas para uma recorrência, para evitar duplicar
     * visitas ao gerar ocorrências mais de uma vez sobre a mesma janela.
     *
     * @return string[]
     */
    public function scheduledDatesForRecurrence(int $tenantId, int $recurrenceId): array;
}
