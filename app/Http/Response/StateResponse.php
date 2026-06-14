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
            'data' => $states->map(fn($state) => self::single($state))
        ];
    }

    public static function single(State $state): array
    {
        return [
            'id' => $state->id,
            'uf' => $state->uf,
            'name' => $state->name,
        ];
    }
}

