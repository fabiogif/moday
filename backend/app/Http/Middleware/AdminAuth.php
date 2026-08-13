<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Verifica se está autenticado no guard admin
        if (!auth()->guard('admin')->check()) {
            return response()->json([
                'success' => false,
                'message' => 'Não autorizado. Autenticação de administrador necessária.',
            ], 401);
        }

        // Verifica se o admin está ativo
        $admin = auth()->guard('admin')->user();
        if (!$admin->is_active) {
            return response()->json([
                'success' => false,
                'message' => 'Conta de administrador inativa.',
            ], 403);
        }

        return $next($request);
    }
}

