<?php

namespace App\Http\Response;

use App\Models\City;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CityResponse
{
    public static function collection(Collection $cities): array
    {
        return [
            'success' => true,
            'data' => $cities->map(fn ($city) => self::single($city))->values()->all(),
        ];
    }

    public static function paginated(LengthAwarePaginator $cities): array
    {
        return [
            'success' => true,
            'data' => $cities,
        ];
    }

    public static function single(City $city): array
    {
        $data = [
            'id' => $city->id,
            'ibge_code' => $city->ibge_code,
            'name' => $city->name,
            'is_capital' => (bool) $city->is_capital,
        ];

        if ($city->relationLoaded('state') && $city->state) {
            $data['state'] = StateResponse::single($city->state);
        }

        return $data;
    }

    public static function stateWithCities(array $data): array
    {
        return [
            'success' => true,
            'data' => [
                'state' => StateResponse::single($data['state']),
                'cities' => $data['cities']->map(fn ($city) => self::single($city))->values()->all(),
            ],
        ];
    }
}
