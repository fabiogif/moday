<?php

namespace App\Repositories\Contracts;

use App\Models\State;
use App\Models\City;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface LocationRepositoryInterface
{
    /**
     * Get all states ordered by name
     */
    public function getAllStates(): Collection;

    /**
     * Find state by UF
     */
    public function findStateByUf(string $uf): ?State;

    /**
     * Get cities by state ID
     */
    public function getCitiesByState(int $stateId): Collection;

    /**
     * Get all cities with pagination
     */
    public function getAllCities(int $perPage = 100, ?string $search = null): LengthAwarePaginator;

    /**
     * Get capital cities
     */
    public function getCapitalCities(): Collection;

    /**
     * Search cities by name
     */
    public function searchCities(string $search, int $limit = 50): Collection;

    /**
     * Get state with cities
     */
    public function getStateWithCities(string $uf): ?State;
}

