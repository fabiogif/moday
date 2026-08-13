<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Tenant;
use App\Models\Product;
use Illuminate\Support\Facades\Log;

class CheckProductLimit
{
    /**
     * Handle an incoming request.
     *
     * Verifica se o tenant atingiu o limite de produtos do plano
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

        // Se não tem plano, considerar como plano Grátis (50 produtos)
        $plan = $tenant->plan;

        if (!$plan) {
            // Plano Grátis padrão
            $maxProducts = 50;
            $planName = 'Grátis';
        } else {
            // Se max_products é null ou muito alto, significa ilimitado
            $maxProducts = $plan->max_products;
            $planName = $plan->name;

            if ($maxProducts === null || $maxProducts >= 999999) {
                // Premium - sem limite
                return $next($request);
            }
        }

        // Contar produtos ativos do tenant
        $productsCount = Product::where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->count();

        if ($productsCount >= $maxProducts) {
            Log::info('Limite de produtos atingido', [
                'tenant_id' => $tenant->id,
                'plan' => $planName,
                'max_products' => $maxProducts,
                'current_products' => $productsCount,
            ]);

            return response()->json([
                'success' => false,
                'message' => "Você atingiu o limite de {$maxProducts} produto(s) do plano {$planName}. Faça upgrade para cadastrar mais produtos.",
                'error_code' => 'PRODUCT_LIMIT_REACHED',
                'current_plan' => $planName,
                'max_products' => $maxProducts,
                'current_products' => $productsCount,
            ], 403);
        }

        return $next($request);
    }
}
