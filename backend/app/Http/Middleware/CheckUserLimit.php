<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Log;

class CheckUserLimit
{
    /**
     * Handle an incoming request.
     *
     * Verifica se o tenant atingiu o limite de usuários do plano
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();
        
        if (!$user || !$user->tenant_id) {
            return response()->json([
                'success' => false,
                'message' => 'Usuário não autenticado',
            ], 401);
        }

        $tenant = Tenant::with('plan')->find($user->tenant_id);
        
        if (!$tenant) {
            return response()->json([
                'success' => false,
                'message' => 'Tenant não encontrado',
            ], 404);
        }

        // Se não tem plano, considerar como plano Grátis (1 usuário)
        $plan = $tenant->plan;
        
        if (!$plan) {
            // Plano Grátis padrão
            $maxUsers = 1;
            $planName = 'Grátis';
        } else {
            // Se max_users é null ou muito alto, significa ilimitado
            $maxUsers = $plan->max_users;
            $planName = $plan->name;
            
            if ($maxUsers === null || $maxUsers >= 999999) {
                // Premium - sem limite
                return $next($request);
            }
        }

        // Contar usuários ativos do tenant
        $usersCount = User::where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->count();

        if ($usersCount >= $maxUsers) {
            Log::info('Limite de usuários atingido', [
                'tenant_id' => $tenant->id,
                'plan' => $planName,
                'max_users' => $maxUsers,
                'current_users' => $usersCount,
            ]);

            return response()->json([
                'success' => false,
                'message' => "Você atingiu o limite de {$maxUsers} usuário(s) do plano {$planName}. Faça upgrade para adicionar mais usuários.",
                'error_code' => 'USER_LIMIT_REACHED',
                'current_plan' => $planName,
                'max_users' => $maxUsers,
                'current_users' => $usersCount,
            ], 403);
        }

        return $next($request);
    }
}

