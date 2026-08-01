<?php

namespace App\Repositories\Contracts;

use App\Models\State;
use App\Models\City;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface LocationRepositoryInterface
{
    public function getAllStates(): Collection;

    public function findStateById(int $id): ?State;

    public function findStateByUf(string $uf): ?State;

    public function findStateByIbgeCode(string $ibgeCode): ?State;

    public function getCitiesByState(int $stateId): Collection;

    public function findCityByIbgeCode(string $ibgeCode): ?City;

    public function findCityByStateAndName(int $stateId, string $name): ?City;

    public function getAllCities(int $perPage = 100, ?string $search = null): LengthAwarePaginator;

    public function getCapitalCities(): Collection;

    public function searchCities(string $search, int $limit = 50): Collection;

    public function getStateWithCities(string $uf): ?State;
}
