<?php

namespace App\Repositories;

use App\Models\State;
use App\Models\City;
use App\Repositories\Concerns\SearchesFullText;
use App\Repositories\Contracts\LocationRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class LocationRepository implements LocationRepositoryInterface
{
    use SearchesFullText;

    /**
     * Get all states ordered by name
     */
    public function getAllStates(): Collection
    {
        return State::orderBy('name')->get(['id', 'uf', 'name']);
    }

    /**
     * Find state by UF
     */
    public function findStateByUf(string $uf): ?State
    {
        return State::where('uf', strtoupper($uf))->first();
    }

    /**
     * Get cities by state ID
     */
    public function getCitiesByState(int $stateId): Collection
    {
        return City::where('state_id', $stateId)
            ->orderBy('name')
            ->get(['id', 'name', 'is_capital']);
    }

    /**
     * Get all cities with pagination
     */
    public function getAllCities(int $perPage = 100, ?string $search = null): LengthAwarePaginator
    {
        $query = City::with('state:id,uf,name')
            ->orderBy('name');

        if ($search) {
            $this->applyFullTextSearch($query, ['name'], $search);
        }

        return $query->paginate($perPage);
    }

    /**
     * Get capital cities
     */
    public function getCapitalCities(): Collection
    {
        return City::with('state:id,uf,name')
            ->where('is_capital', true)
            ->orderBy('name')
            ->get(['id', 'state_id', 'name']);
    }

    /**
     * Search cities by name
     */
    public function searchCities(string $search, int $limit = 50): Collection
    {
        $query = City::with('state:id,uf,name');
        $this->applyFullTextSearch($query, ['name'], $search);

        return $query->orderBy('name')
            ->limit($limit)
            ->get(['id', 'state_id', 'name', 'is_capital']);
    }

    /**
     * Get state with cities
     */
    public function getStateWithCities(string $uf): ?State
    {
        return State::with(['cities' => function ($query) {
            $query->orderBy('name')->select(['id', 'state_id', 'name', 'is_capital']);
        }])
        ->where('uf', strtoupper($uf))
        ->first();
    }
}

