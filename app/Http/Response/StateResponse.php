<?php

namespace App\Http\Response;

use App\Models\State;
use Illuminate\Database\Eloquent\Collection;

class StateResponse
{
    public static function collection(Collection $states): array
    {
        return [
            'success' => true,
            'data' => $states->map(fn ($state) => self::single($state))->values()->all(),
        ];
    }

    public static function single(State $state): array
    {
        return [
            'id' => $state->id,
            'ibge_code' => $state->ibge_code,
            'uf' => $state->uf,
            'name' => $state->name,
            'region' => $state->region,
        ];
    }
}
