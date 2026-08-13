<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

class CheckTenantBlocked
{
    /**
     * Verifica se o tenant do usuário está bloqueado
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Se não há usuário autenticado, deixa passar (outros middlewares cuidam da autenticação)
        if (!$user) {
            return $next($request);
        }

        // Carrega o tenant se ainda não foi carregado
        if (!$user->relationLoaded('tenant')) {
            $user->load('tenant');
        }

        // Verifica se o tenant existe e está bloqueado
        if ($user->tenant && $user->tenant->is_blocked) {
            Log::warning('Request blocked: Tenant is blocked', [
                'user_id' => $user->id,
                'tenant_id' => $user->tenant->id,
                'tenant_name' => $user->tenant->name,
                'blocked_reason' => $user->tenant->blocked_reason,
                'route' => $request->path(),
            ]);

            $message = 'Seu acesso foi temporariamente bloqueado.';
            if ($user->tenant->blocked_reason) {
                $message .= ' Motivo: ' . $user->tenant->blocked_reason;
            }
            $message .= ' Entre em contato com o suporte.';

            return response()->json([
                'success' => false,
                'message' => $message,
                'error' => 'tenant_blocked',
                'blocked_at' => $user->tenant->blocked_at,
                'blocked_reason' => $user->tenant->blocked_reason,
            ], 403);
        }

        return $next($request);
    }
}

