<?php

namespace App\Services;

use App\Classes\ApiResponseClass;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Support\Facades\Auth;

class AuthTenantService
{
    /**
     * Ensure there is an authenticated user with a tenant.
     * Returns the authenticated user and tenant_id.
     * Throws HttpResponseException with standardized response when invalid.
     */
    public function requireAuthenticatedTenant(): array
    {
        $user = Auth::user();

        if (!$user) {
            throw new HttpResponseException(ApiResponseClass::unauthorized('Usuário não autenticado'));
        }

        if (!$user->tenant_id) {
            throw new HttpResponseException(ApiResponseClass::forbidden('Usuário não possui tenant associado'));
        }

        return [$user, (int) $user->tenant_id];
    }
}


