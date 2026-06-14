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
            'data' => $cities->map(fn($city) => self::single($city))
        ];
    }

    public static function paginated(LengthAwarePaginator $cities): array
    {
        return [
            'success' => true,
            'data' => $cities
        ];
    }

    public static function single(City $city): array
    {
        $data = [
            'id' => $city->id,
            'name' => $city->name,
            'is_capital' => $city->is_capital,
        ];

        if ($city->relationLoaded('state')) {
            $data['state'] = [
                'id' => $city->state->id,
                'uf' => $city->state->uf,
                'name' => $city->state->name,
            ];
        }

        return $data;
    }

    public static function stateWithCities(array $data): array
    {
        return [
            'success' => true,
            'data' => [
                'state' => StateResponse::single($data['state']),
                'cities' => $data['cities']->map(fn($city) => self::single($city))
            ]
        ];
    }
}

