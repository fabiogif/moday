<?php

namespace App\Services;

use App\Models\City;
use App\Models\State;
use App\Repositories\Contracts\LocationRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

readonly class LocationService
{
    private const STATES_CACHE_KEY = 'location.states';
    private const STATES_CACHE_TTL = 86400; // 24h
    private const CITIES_CACHE_TTL = 86400;

    public function __construct(
        private LocationRepositoryInterface $locationRepository
    ) {}

    public function getAllStates(): Collection
    {
        return Cache::remember(self::STATES_CACHE_KEY, self::STATES_CACHE_TTL, function () {
            return $this->locationRepository->getAllStates();
        });
    }

    public function getCitiesByStateId(int $stateId): array
    {
        $state = $this->locationRepository->findStateById($stateId);

        if (!$state) {
            throw new \Exception('Estado não encontrado', 404);
        }

        $cities = Cache::remember(
            "location.cities.{$stateId}",
            self::CITIES_CACHE_TTL,
            fn () => $this->locationRepository->getCitiesByState($stateId)
        );

        return [
            'state' => $state,
            'cities' => $cities,
        ];
    }

    public function getCitiesByStateUf(string $uf): array
    {
        $state = $this->locationRepository->findStateByUf($uf);

        if (!$state) {
            throw new \Exception('Estado não encontrado', 404);
        }

        return $this->getCitiesByStateId($state->id);
    }

    /**
     * Resolve state key that may be numeric id or UF (2 letters).
     */
    public function getCitiesByStateKey(string $stateKey): array
    {
        if (ctype_digit($stateKey)) {
            return $this->getCitiesByStateId((int) $stateKey);
        }

        return $this->getCitiesByStateUf($stateKey);
    }

    public function getAllCities(int $perPage = 100, ?string $search = null): LengthAwarePaginator
    {
        return $this->locationRepository->getAllCities($perPage, $search);
    }

    public function getCapitalCities(): Collection
    {
        return $this->locationRepository->getCapitalCities();
    }

    public function searchCities(string $search): Collection
    {
        if (strlen($search) < 2) {
            throw new \Exception('Digite pelo menos 2 caracteres para pesquisar', 400);
        }

        return $this->locationRepository->searchCities($search);
    }

    public function resolveCityFromCep(?string $ibgeCode, ?string $uf, ?string $cityName): ?City
    {
        if ($ibgeCode) {
            $city = $this->locationRepository->findCityByIbgeCode($ibgeCode);
            if ($city) {
                return $city;
            }
        }

        if (!$uf || !$cityName) {
            return null;
        }

        $state = $this->locationRepository->findStateByUf($uf);
        if (!$state) {
            return null;
        }

        return $this->locationRepository->findCityByStateAndName($state->id, $cityName);
    }

    public function findStateByUf(string $uf): ?State
    {
        return $this->locationRepository->findStateByUf($uf);
    }
}
