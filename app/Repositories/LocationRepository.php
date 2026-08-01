<?php

namespace App\Repositories;

use App\Models\State;
use App\Models\City;
use App\Repositories\Concerns\SearchesFullText;
use App\Repositories\Contracts\LocationRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class LocationRepository implements LocationRepositoryInterface
{
    use SearchesFullText;

    public function getAllStates(): Collection
    {
        return State::query()
            ->orderBy('name')
            ->get(['id', 'ibge_code', 'uf', 'name', 'region']);
    }

    public function findStateById(int $id): ?State
    {
        return State::query()->find($id);
    }

    public function findStateByUf(string $uf): ?State
    {
        return State::query()->where('uf', strtoupper($uf))->first();
    }

    public function findStateByIbgeCode(string $ibgeCode): ?State
    {
        return State::query()->where('ibge_code', $ibgeCode)->first();
    }

    public function getCitiesByState(int $stateId): Collection
    {
        return City::query()
            ->where('state_id', $stateId)
            ->orderBy('name')
            ->get(['id', 'state_id', 'ibge_code', 'name', 'is_capital']);
    }

    public function findCityByIbgeCode(string $ibgeCode): ?City
    {
        return City::query()
            ->with('state:id,ibge_code,uf,name,region')
            ->where('ibge_code', $ibgeCode)
            ->first();
    }

    public function findCityByStateAndName(int $stateId, string $name): ?City
    {
        $normalized = $this->normalizeName($name);

        $cities = City::query()
            ->where('state_id', $stateId)
            ->get(['id', 'state_id', 'ibge_code', 'name', 'is_capital']);

        $exact = $cities->first(fn (City $city) => mb_strtolower($city->name) === mb_strtolower($name));
        if ($exact) {
            return $exact;
        }

        return $cities->first(
            fn (City $city) => $this->normalizeName($city->name) === $normalized
        );
    }

    public function getAllCities(int $perPage = 100, ?string $search = null): LengthAwarePaginator
    {
        $query = City::with('state:id,ibge_code,uf,name,region')
            ->orderBy('name');

        if ($search) {
            $this->applyFullTextSearch($query, ['name'], $search);
        }

        return $query->paginate($perPage);
    }

    public function getCapitalCities(): Collection
    {
        return City::with('state:id,ibge_code,uf,name,region')
            ->where('is_capital', true)
            ->orderBy('name')
            ->get(['id', 'state_id', 'ibge_code', 'name', 'is_capital']);
    }

    public function searchCities(string $search, int $limit = 50): Collection
    {
        $query = City::with('state:id,ibge_code,uf,name,region');
        $this->applyFullTextSearch($query, ['name'], $search);

        return $query->orderBy('name')
            ->limit($limit)
            ->get(['id', 'state_id', 'ibge_code', 'name', 'is_capital']);
    }

    public function getStateWithCities(string $uf): ?State
    {
        return State::with(['cities' => function ($query) {
            $query->orderBy('name')->select(['id', 'state_id', 'ibge_code', 'name', 'is_capital']);
        }])
            ->where('uf', strtoupper($uf))
            ->first();
    }

    private function normalizeName(string $name): string
    {
        return Str::lower(Str::ascii(trim($name)));
    }
}
