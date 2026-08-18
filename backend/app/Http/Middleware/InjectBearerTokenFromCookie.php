<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class InjectBearerTokenFromCookie
{
    /**
     * Copia o token de um cookie HttpOnly para o header Authorization quando
     * a request não trouxe o header. Mantém o header explícito sempre
     * prioritário, então o app mobile (que sempre envia Authorization) não é
     * afetado. Funciona igual para guards JWT e para tokens opacos (Sanctum),
     * já que só manipula o header antes do guard resolver o usuário.
     */
    public function handle(Request $request, Closure $next, string $cookieName): Response
    {
        if ($request->hasHeader('Authorization')) {
            return $next($request);
        }

        $names = [$cookieName];
        // O frontend espelha o JWT em `auth-token` (lido pelo middleware Next).
        // O cookie HttpOnly da API é `auth_token`. Após o cadastro o HttpOnly
        // pode ainda não existir; aceitar os dois evita 401 no reenvio/logout.
        if ($cookieName === 'auth_token') {
            $names[] = 'auth-token';
        }

        foreach ($names as $name) {
            $token = $request->cookie($name);
            if (is_string($token) && $token !== '') {
                $request->headers->set('Authorization', 'Bearer ' . $token);
                break;
            }
        }

        return $next($request);
    }
}
