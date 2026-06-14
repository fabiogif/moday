<?php

namespace App\Services;

use App\Repositories\Contracts\LocationRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

readonly class LocationService
{
    public function __construct(
        private LocationRepositoryInterface $locationRepository
    ) {}

    /**
     * Get all states
     */
    public function getAllStates(): Collection
    {
        return $this->locationRepository->getAllStates();
    }

    /**
     * Get cities by state UF
     */
    public function getCitiesByStateUf(string $uf): array
    {
        $state = $this->locationRepository->findStateByUf($uf);

        if (!$state) {
            throw new \Exception('Estado não encontrado', 404);
        }

        $cities = $this->locationRepository->getCitiesByState($state->id);

        return [
            'state' => $state,
            'cities' => $cities
        ];
    }

    /**
     * Get all cities with pagination
     */
    public function getAllCities(int $perPage = 100, ?string $search = null): LengthAwarePaginator
    {
        return $this->locationRepository->getAllCities($perPage, $search);
    }

    /**
     * Get capital cities
     */
    public function getCapitalCities(): Collection
    {
        return $this->locationRepository->getCapitalCities();
    }

    /**
     * Search cities by name
     */
    public function searchCities(string $search): Collection
    {
        if (strlen($search) < 2) {
            throw new \Exception('Digite pelo menos 2 caracteres para pesquisar', 400);
        }

        return $this->locationRepository->searchCities($search);
    }
}

