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
        if (!$request->hasHeader('Authorization') && $request->hasCookie($cookieName)) {
            $request->headers->set('Authorization', 'Bearer ' . $request->cookie($cookieName));
        }

        return $next($request);
    }
}
