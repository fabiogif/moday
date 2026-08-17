<?php

namespace App\Http\Middleware;

use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Http\Exceptions\HttpResponseException;
use App\Classes\ApiResponseClass;

class Authenticate extends Middleware
{
    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        return null;
    }

    protected function authenticate($request, array $guards)
    {
        try {
            parent::authenticate($request, $guards);
        } catch (AuthenticationException $exception) {
            $message = $request->header('Authorization') ? 'Token inválido' : 'Token não fornecido';

            throw new HttpResponseException(ApiResponseClass::unauthorized($message));
        }
    }
}
