<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'Não autenticado.',
                'error' => 'Não autenticado.'
            ], 401);
        }

        if (!$user->hasPermissionTo($permission)) {
            return response()->json([
                'message' => 'Permissões insuficientes.',
                'error' => 'INSUFFICIENT_PERMISSIONS',
                'required_permission' => $permission
            ], 403);
        }

        return $next($request);
    }
}
