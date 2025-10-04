<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckAnyPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, ...$permissions): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'message' => 'Não autenticado.',
                'error' => 'UNAUTHENTICATED'
            ], 401);
        }

        if (!$user->hasAnyPermission($permissions)) {
            return response()->json([
                'message' => 'Permissões insuficientes.',
                'error' => 'INSUFFICIENT_PERMISSIONS',
                'required_permissions' => $permissions
            ], 403);
        }

        return $next($request);
    }
}
