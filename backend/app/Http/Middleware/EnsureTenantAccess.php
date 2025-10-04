<?php

namespace App\Http\Middleware;

use App\Classes\ApiResponseClass;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantAccess
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();
        
        if (!$user) {
            return ApiResponseClass::unauthorized('Usuário não autenticado');
        }
        
        if (!$user->tenant_id) {
            return ApiResponseClass::forbidden('Usuário não possui tenant associado');
        }
        
        // Adicionar tenant_id ao request para uso nos controllers
        $request->merge(['tenant_id' => $user->tenant_id]);
        
        return $next($request);
    }
}
