<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminPermission
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $permission
     */
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $admin = auth()->guard('admin')->user();

        if (!$admin) {
            return response()->json([
                'success' => false,
                'message' => 'Não autorizado.',
            ], 401);
        }

        if (!$admin->hasPermission($permission)) {
            return response()->json([
                'success' => false,
                'message' => 'Você não tem permissão para executar esta ação.',
                'required_permission' => $permission,
                'your_role' => $admin->role,
            ], 403);
        }

        return $next($request);
    }
}

