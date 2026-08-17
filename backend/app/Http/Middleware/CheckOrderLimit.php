<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Tenant;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CheckOrderLimit
{
    /**
     * Handle an incoming request.
     *
     * Verifica se o tenant atingiu o limite de pedidos do mês
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

        // Se não tem plano, considerar como plano Grátis (30 pedidos)
        $plan = $tenant->plan;
        
        if (!$plan) {
            // Plano Grátis padrão
            $maxOrders = 30;
            $planName = 'Grátis';
        } else {
            // Se max_orders_per_month é null, significa ilimitado
            $maxOrders = $plan->max_orders_per_month;
            $planName = $plan->name;
            
            if ($maxOrders === null) {
                // Premium - sem limite
                return $next($request);
            }
        }

        // Contar pedidos do mês atual
        $currentMonth = now()->startOfMonth();
        $currentMonthEnd = now()->endOfMonth();
        
        $ordersCount = Order::where('tenant_id', $tenant->id)
            ->whereBetween('created_at', [$currentMonth, $currentMonthEnd])
            ->count();

        if ($ordersCount >= $maxOrders) {
            Log::info('Limite de pedidos atingido', [
                'tenant_id' => $tenant->id,
                'plan' => $plan ? $plan->name : 'Grátis',
                'max_orders' => $maxOrders,
                'current_orders' => $ordersCount,
                'month' => now()->format('Y-m'),
            ]);

            return response()->json([
                'success' => false,
                'message' => "Você atingiu o limite de {$maxOrders} pedidos do mês. Faça upgrade para continuar criando pedidos.",
                'error_code' => 'ORDER_LIMIT_REACHED',
                'current_plan' => $planName,
                'max_orders' => $maxOrders,
                'current_orders' => $ordersCount,
                'month' => now()->format('Y-m'),
            ], 403);
        }

        return $next($request);
    }
}

