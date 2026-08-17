<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Tenant;

class CheckTrialStatus
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Se não estiver autenticado, deixa o middleware de auth tratar
        if (!auth()->check()) {
            return $next($request);
        }

        $user = auth()->user();
        
        // Se não tiver tenant, não precisa verificar
        if (!$user->tenant_id) {
            return $next($request);
        }

        $tenant = Tenant::with('plan')->find($user->tenant_id);

        // Se não encontrou o tenant, erro
        if (!$tenant) {
            return response()->json([
                'error' => 'Tenant não encontrado',
                'message' => 'A empresa associada a este usuário não foi encontrada.'
            ], 404);
        }

        // Plano Grátis permanente não passa por bloqueio de trial
        if ($tenant->isOnFreePlan()) {
            $request->attributes->set('trial_info', $tenant->toTrialStatusArray());

            return $next($request);
        }

        // Verifica se tem acesso
        if (!$tenant->canAccess()) {
            // Se trial expirado, bloqueia com mensagem específica
            if ($tenant->isTrialExpired() || $tenant->account_status === 'expired') {
                return response()->json([
                    'error' => 'trial_expired',
                    'message' => 'Seu período de teste gratuito expirou. Para continuar usando o sistema, escolha um plano.',
                    'trial_status' => [
                        'is_trial' => false,
                        'is_active' => false,
                        'is_expired' => true,
                        'days_remaining' => 0,
                        'expires_at' => $tenant->trial_expires_at?->format('Y-m-d H:i:s'),
                        'needs_payment' => true,
                    ]
                ], 403);
            }

            // Se conta suspensa
            if ($tenant->account_status === 'suspended') {
                return response()->json([
                    'error' => 'account_suspended',
                    'message' => 'Sua conta foi suspensa. Entre em contato com o suporte.',
                    'trial_status' => [
                        'is_trial' => false,
                        'is_active' => false,
                        'is_expired' => false,
                        'is_suspended' => true,
                        'needs_payment' => false,
                    ]
                ], 403);
            }

            // Outro motivo de bloqueio
            return response()->json([
                'error' => 'access_denied',
                'message' => 'Acesso negado. Verifique o status da sua conta.',
            ], 403);
        }

        $request->attributes->set('trial_info', $tenant->toTrialStatusArray());

        return $next($request);
    }
}

