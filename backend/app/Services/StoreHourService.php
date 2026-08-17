<?php

namespace App\Services;

use App\Repositories\Contracts\StoreHourRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Carbon\CarbonInterface;

class StoreHourService
{
    public function __construct(
        private readonly StoreHourRepositoryInterface $storeHourRepository
    ) {}

    public function getAllStoreHours(int $tenantId, array $filters = [])
    {
        return $this->storeHourRepository->getAllByTenant($tenantId, $filters);
    }

    public function getStoreHourByUuid(string $uuid)
    {
        return $this->storeHourRepository->getByUuid($uuid);
    }

    public function createStoreHour(array $data, int $tenantId)
    {
        DB::beginTransaction();
        try {
            if (isset($data['is_always_open']) && $data['is_always_open']) {
                // Se é "sempre aberto", desativar todos os horários personalizados anteriores
                $this->deactivateAllCustomHours($tenantId);

                $data['tenant_id'] = $tenantId;
                $data['day_of_week'] = null;
                $data['start_time'] = null;
                $data['end_time'] = null;
                $data['start_time_2'] = null;
                $data['end_time_2'] = null;
            } else {
                // Validar horários personalizados
                if (!array_key_exists('day_of_week', $data) || ($data['day_of_week'] === null)) {
                    throw new \Exception('O dia da semana é obrigatório para horários personalizados.');
                }

                if (!array_key_exists('start_time', $data) || !array_key_exists('end_time', $data)) {
                    throw new \Exception('Os horários de início e fim são obrigatórios.');
                }

                $startTime = $this->parseTime($data['start_time']);
                $endTime = $this->parseTime($data['end_time']);

                if (!$startTime || !$endTime) {
                    throw new \Exception('Os horários de início e fim são obrigatórios.');
                }

                if ($startTime->greaterThanOrEqualTo($endTime)) {
                    throw new \Exception('O horário de início deve ser anterior ao horário de fim.');
                }

                $startTime2 = $this->parseTime($data['start_time_2'] ?? null);
                $endTime2 = $this->parseTime($data['end_time_2'] ?? null);

                if ($startTime2 || $endTime2) {
                    if (!$startTime2 || !$endTime2) {
                        throw new \Exception('Se usar intervalo, ambos os horários do 2º período são obrigatórios.');
                    }

                    if ($startTime2->greaterThanOrEqualTo($endTime2)) {
                        throw new \Exception('O horário de início do 2º período deve ser anterior ao seu término.');
                    }

                    if ($startTime2->lessThanOrEqualTo($endTime)) {
                        throw new \Exception('O 2º período deve começar após o término do 1º período.');
                    }
                }

                // Verificar sobreposição de horários
                if ($this->storeHourRepository->checkOverlap(
                    $tenantId,
                    $data['day_of_week'],
                    $startTime->format('H:i:s'),
                    $endTime->format('H:i:s')
                )) {
                    throw new \Exception('Já existe um horário cadastrado que sobrepõe este período.');
                }

                // Desativar configuração "sempre aberto" se existir
                $this->deactivateAlwaysOpen($tenantId);

                $data['tenant_id'] = $tenantId;
                $data['is_always_open'] = false;
                $data['start_time'] = $startTime->format('H:i:s');
                $data['end_time'] = $endTime->format('H:i:s');
                $data['start_time_2'] = $startTime2?->format('H:i:s');
                $data['end_time_2'] = $endTime2?->format('H:i:s');
            }

            $storeHour = $this->storeHourRepository->create($data);

            DB::commit();
            Log::info('Horário de funcionamento criado', ['store_hour_id' => $storeHour->id, 'tenant_id' => $tenantId]);

            return $storeHour;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erro ao criar horário de funcionamento: ' . $e->getMessage());
            throw $e;
        }
    }

    public function updateStoreHour(int $id, array $data)
    {
        DB::beginTransaction();
        try {
            $storeHour = $this->storeHourRepository->getById($id);
            if (!$storeHour) {
                throw new \Exception('Horário de funcionamento não encontrado.');
            }

            $tenantId = $storeHour->tenant_id;
            $referenceEndTime = $this->parseTime($data['end_time'] ?? $storeHour->end_time);

            // Validações para horários personalizados
            if (!isset($data['is_always_open']) || !$data['is_always_open']) {
                if (array_key_exists('start_time', $data) || array_key_exists('end_time', $data)) {
                    if (!array_key_exists('start_time', $data) || !array_key_exists('end_time', $data)) {
                        throw new \Exception('Os horários de início e fim são obrigatórios.');
                    }

                    $startTime = $this->parseTime($data['start_time']);
                    $endTime = $this->parseTime($data['end_time']);

                    if (!$startTime || !$endTime) {
                        throw new \Exception('Os horários de início e fim são obrigatórios.');
                    }

                    if ($startTime->greaterThanOrEqualTo($endTime)) {
                        throw new \Exception('O horário de início deve ser anterior ao horário de fim.');
                    }

                    $dayOfWeek = $data['day_of_week'] ?? $storeHour->day_of_week;
                    if ($this->storeHourRepository->checkOverlap(
                        $tenantId,
                        $dayOfWeek,
                        $startTime->format('H:i:s'),
                        $endTime->format('H:i:s'),
                        $id
                    )) {
                        throw new \Exception('Já existe um horário cadastrado que sobrepõe este período.');
                    }

                    $data['start_time'] = $startTime->format('H:i:s');
                    $data['end_time'] = $endTime->format('H:i:s');
                    $referenceEndTime = $endTime;
                }

                if (array_key_exists('start_time_2', $data) || array_key_exists('end_time_2', $data)) {
                    $startTime2 = $this->parseTime($data['start_time_2'] ?? null);
                    $endTime2 = $this->parseTime($data['end_time_2'] ?? null);

                    if ($startTime2 || $endTime2) {
                        if (!$startTime2 || !$endTime2) {
                            throw new \Exception('Se usar intervalo, ambos os horários do 2º período são obrigatórios.');
                        }

                        if ($startTime2->greaterThanOrEqualTo($endTime2)) {
                            throw new \Exception('O horário de início do 2º período deve ser anterior ao seu término.');
                        }

                        if ($referenceEndTime && $startTime2->lessThanOrEqualTo($referenceEndTime)) {
                            throw new \Exception('O 2º período deve começar após o término do 1º período.');
                        }

                        $data['start_time_2'] = $startTime2->format('H:i:s');
                        $data['end_time_2'] = $endTime2->format('H:i:s');
                    } else {
                        $data['start_time_2'] = null;
                        $data['end_time_2'] = null;
                    }
                }
            } else {
                $data['day_of_week'] = null;
                $data['start_time'] = null;
                $data['end_time'] = null;
                $data['start_time_2'] = null;
                $data['end_time_2'] = null;
            }

            $updated = $this->storeHourRepository->update($id, $data);

            DB::commit();
            Log::info('Horário de funcionamento atualizado', ['store_hour_id' => $id]);

            return $updated;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erro ao atualizar horário de funcionamento: ' . $e->getMessage());
            throw $e;
        }
    }

    public function deleteStoreHour(int $id)
    {
        DB::beginTransaction();
        try {
            $storeHour = $this->storeHourRepository->getById($id);
            if (!$storeHour) {
                throw new \Exception('Horário de funcionamento não encontrado.');
            }

            $result = $this->storeHourRepository->delete($id);

            DB::commit();
            Log::info('Horário de funcionamento excluído', ['store_hour_id' => $id]);

            return $result;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erro ao excluir horário de funcionamento: ' . $e->getMessage());
            throw $e;
        }
    }

    public function getStoreHoursByDay(int $tenantId, int $dayOfWeek)
    {
        return $this->storeHourRepository->getByDayOfWeek($tenantId, $dayOfWeek);
    }

    public function getStoreHoursByDeliveryType(int $tenantId, string $deliveryType)
    {
        return $this->storeHourRepository->getByDeliveryType($tenantId, $deliveryType);
    }

    public function isStoreOpen(int $tenantId, ?string $deliveryType = 'both'): bool
    {
        // Verificar se a loja está sempre aberta
        if ($this->storeHourRepository->isAlwaysOpen($tenantId)) {
            return true;
        }

        $now = now();
        $dayOfWeek = $now->dayOfWeek; // 0 = domingo, 6 = sábado
        $currentTime = $now->format('H:i');

        $storeHours = $this->storeHourRepository->getByDayOfWeek($tenantId, $dayOfWeek);

        foreach ($storeHours as $hour) {
            // Verificar se o tipo de entrega é compatível
            if ($deliveryType !== 'both' && $hour->delivery_type !== 'both' && $hour->delivery_type !== $deliveryType) {
                continue;
            }

            // Verificar primeiro período
            if ($hour->start_time && $hour->end_time) {
                $startTime = $hour->start_time instanceof \DateTimeInterface
                    ? $hour->start_time->format('H:i')
                    : (string) $hour->start_time;
                $endTime = $hour->end_time instanceof \DateTimeInterface
                    ? $hour->end_time->format('H:i')
                    : (string) $hour->end_time;

                if ($this->timeWindowContains($currentTime, $startTime, $endTime)) {
                return true;
                }
            }

            // Verificar segundo período (se existir)
            if ($hour->start_time_2 && $hour->end_time_2) {
                $startTime2 = $hour->start_time_2 instanceof \DateTimeInterface
                    ? $hour->start_time_2->format('H:i')
                    : (string) $hour->start_time_2;
                $endTime2 = $hour->end_time_2 instanceof \DateTimeInterface
                    ? $hour->end_time_2->format('H:i')
                    : (string) $hour->end_time_2;

                if ($this->timeWindowContains($currentTime, $startTime2, $endTime2)) {
                    return true;
                }
            }
        }

        return false;
    }

    private function timeWindowContains(string $currentTime, string $startTime, string $endTime): bool
    {
        if ($startTime <= $endTime) {
            return $currentTime >= $startTime && $currentTime <= $endTime;
        }

        // Período atravessa a meia-noite
        return $currentTime >= $startTime || $currentTime <= $endTime;
    }

    /**
     * Desativa todos os horários personalizados de um tenant
     */
    private function deactivateAllCustomHours(int $tenantId): void
    {
        $customHours = $this->storeHourRepository->getAllByTenant($tenantId, [
            'is_active' => true
        ]);

        foreach ($customHours as $hour) {
            if (!$hour->is_always_open) {
                $this->storeHourRepository->update($hour->id, ['is_active' => false]);
            }
        }
    }

    /**
     * Desativa a configuração "sempre aberto"
     */
    private function deactivateAlwaysOpen(int $tenantId): void
    {
        $alwaysOpenHours = $this->storeHourRepository->getAllByTenant($tenantId, [
            'is_active' => true
        ]);

        foreach ($alwaysOpenHours as $hour) {
            if ($hour->is_always_open) {
                $this->storeHourRepository->update($hour->id, ['is_active' => false]);
            }
        }
    }

    /**
     * Configura a loja como sempre aberta
     */
    public function setAlwaysOpen(int $tenantId, string $deliveryType = 'both')
    {
        return $this->createStoreHour([
            'is_always_open' => true,
            'delivery_type' => $deliveryType,
            'is_active' => true,
        ], $tenantId);
    }

    /**
     * Remove a configuração "sempre aberto"
     */
    public function removeAlwaysOpen(int $tenantId)
    {
        DB::beginTransaction();
        try {
            $this->deactivateAlwaysOpen($tenantId);

            DB::commit();
            Log::info('Configuração "sempre aberto" removida', ['tenant_id' => $tenantId]);

            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erro ao remover configuração "sempre aberto": ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Retorna estatísticas dos horários de funcionamento
     */
    public function getStats(int $tenantId)
    {
        $isAlwaysOpen = $this->storeHourRepository->isAlwaysOpen($tenantId);
        $activeHours = $this->storeHourRepository->getActiveHours($tenantId);

        $daysWithHours = $activeHours->pluck('day_of_week')->unique()->count();

        return [
            'is_always_open' => $isAlwaysOpen,
            'total_hours' => $activeHours->count(),
            'days_configured' => $daysWithHours,
            'has_delivery' => $activeHours->where('delivery_type', 'delivery')->count() > 0 ||
                             $activeHours->where('delivery_type', 'both')->count() > 0,
            'has_pickup' => $activeHours->where('delivery_type', 'pickup')->count() > 0 ||
                           $activeHours->where('delivery_type', 'both')->count() > 0,
        ];
    }

    private function parseTime(null|string|\DateTimeInterface $time): ?Carbon
    {
        if ($time instanceof CarbonInterface) {
            return $time->copy();
        }

        if ($time instanceof \DateTimeInterface) {
            return Carbon::instance($time);
        }

        if (is_string($time)) {
            $trimmed = trim($time);
            if ($trimmed === '') {
                return null;
            }

            return Carbon::parse($trimmed);
        }

        return null;
    }
}

