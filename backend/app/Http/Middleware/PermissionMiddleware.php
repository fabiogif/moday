<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PermissionMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @param  string  $permission
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next, string $permission)
    {
        $user = $request->user();

        // Verificar se o usuário está autenticado
        if (!$user) {
            return response()->json([
                'message' => 'Usuário não autenticado'
            ], 401);
        }

        // Verificar se o usuário tem a permissão
        if (!$user->hasPermissionTo($permission)) {
            Log::warning('Tentativa de acesso negada', [
                'user_id' => $user->id,
                'permission' => $permission,
                'route' => $request->route()?->getName(),
                'url' => $request->url(),
                'method' => $request->method(),
            ]);

            return response()->json([
                'message' => 'Acesso negado. Permissão necessária: ' . $permission
            ], 403);
        }

        // Log de acesso autorizado (apenas em debug)
        if (config('app.debug', false)) {
            Log::debug('Acesso autorizado', [
                'user_id' => $user->id,
                'permission' => $permission,
                'route' => $request->route()?->getName(),
            ]);
        }

        return $next($request);
    }
}
