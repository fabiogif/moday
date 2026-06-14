<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Tenant;
use App\Models\Plan;
use App\Services\PlanFeatureService;
use Illuminate\Support\Facades\Log;

class CheckPlanFeatures
{
    public function __construct(private readonly PlanFeatureService $featureService) {}

    public function handle(Request $request, Closure $next, string $feature): Response
    {
        $user = auth()->user();

        if (!$user || !$user->tenant_id) {
            return response()->json(['success' => false, 'message' => 'Usuário não autenticado'], 401);
        }

        $tenant = Tenant::with('plan')->find($user->tenant_id);

        if (!$tenant) {
            return response()->json(['success' => false, 'message' => 'Tenant não encontrado'], 404);
        }

        if (!$tenant->plan) {
            return response()->json([
                'success'       => false,
                'message'       => 'Seu plano não possui acesso a esta funcionalidade. Faça upgrade para continuar.',
                'error_code'    => 'PLAN_FEATURE_NOT_AVAILABLE',
                'required_plan' => 'Básico',
            ], 403);
        }

        $hasAccess = $this->featureService->hasFeature($user->tenant_id, $feature);

        if (!$hasAccess) {
            $plan     = $tenant->plan;
            $featureLabel = $this->resolveFeatureLabel($feature);

            Log::info('Acesso negado a feature', [
                'tenant_id' => $tenant->id,
                'plan'      => $plan->name,
                'feature'   => $feature,
                'user_id'   => $user->id,
            ]);

            $upgradePlan = $this->suggestUpgradePlan($feature);

            $message = $upgradePlan
                ? "Seu plano ({$plan->name}) não possui acesso ao módulo de {$featureLabel}. Faça upgrade para o plano {$upgradePlan} para continuar."
                : "Seu plano ({$plan->name}) não possui acesso ao módulo de {$featureLabel}. Faça upgrade do plano para continuar.";

            return response()->json([
                'success'       => false,
                'message'       => $message,
                'error_code'    => 'PLAN_FEATURE_NOT_AVAILABLE',
                'current_plan'  => $plan->name,
                'required_plan' => $upgradePlan,
                'feature'       => $featureLabel,
            ], 403);
        }

        return $next($request);
    }

    private function resolveFeatureLabel(string $featureKey): string
    {
        $definition = \App\Models\FeatureDefinition::where('key', $featureKey)->value('name');
        if ($definition) {
            return $definition;
        }

        // Legacy fallback
        return match ($featureKey) {
            'marketing'              => 'Marketing',
            'reports'                => 'Relatórios',
            'order_completion_email' => 'E-mail de confirmação de pedido',
            default                  => ucfirst(str_replace('_', ' ', $featureKey)),
        };
    }

    private function suggestUpgradePlan(string $featureKey): ?string
    {
        return Plan::whereHas('features', fn ($q) =>
            $q->where('feature_key', $featureKey)->where('is_enabled', true)
        )
        ->where('is_active', true)
        ->orderBy('price')
        ->value('name');
    }
}
