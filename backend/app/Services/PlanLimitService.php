<?php

namespace App\Services;

use App\Repositories\Contracts\TenantRepositoryInterface;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\Contracts\ProductUsageRepositoryInterface;
use App\Repositories\Contracts\OrderUsageRepositoryInterface;

readonly class PlanLimitService
{
    public function __construct(
        private TenantRepositoryInterface $tenantRepository,
        private UserRepositoryInterface $userRepository,
        private ProductUsageRepositoryInterface $productUsageRepository,
        private OrderUsageRepositoryInterface $orderUsageRepository,
    ) {}
    /**
     * Verifica se algum limite foi atingido para o tenant
     */
    public function checkLimits(int $tenantId): array
    {
        $tenant = $this->tenantRepository->getById($tenantId);
        
        if (!$tenant) {
            return [
                'has_limit_reached' => false,
                'reached_limits' => [],
                'current_usage' => [],
                'plan_limits' => [],
                'plan_name' => null,
                'message' => 'Tenant não encontrado',
            ];
        }

        $tenant->loadMissing('plan');
        $plan = $tenant->plan;
        
        // Se não tem plano, considerar como plano Grátis
        if (!$plan) {
            $planLimits = [
                'max_users' => 1,
                'max_products' => 50,
                'max_orders_per_month' => 30,
            ];
            $planName = 'Grátis';
        } else {
            $planLimits = [
                'max_users' => $plan->max_users,
                'max_products' => $plan->max_products,
                'max_orders_per_month' => $plan->max_orders_per_month,
            ];
            $planName = $plan->name;
        }

        // Verificar uso atual
        $currentUsage = $this->getCurrentUsage($tenantId);
        
        // Verificar quais limites foram atingidos
        $reachedLimits = [];
        $messages = [];

        // Verificar limite de usuários
        if ($planLimits['max_users'] !== null && $planLimits['max_users'] < 999999) {
            if ($currentUsage['users'] >= $planLimits['max_users']) {
                $reachedLimits[] = 'users';
                $messages[] = "Você atingiu o limite de {$planLimits['max_users']} usuário(s).";
            }
        }

        // Verificar limite de produtos
        if ($planLimits['max_products'] !== null && $planLimits['max_products'] < 999999) {
            if ($currentUsage['products'] >= $planLimits['max_products']) {
                $reachedLimits[] = 'products';
                $messages[] = "Você atingiu o limite de {$planLimits['max_products']} produto(s).";
            }
        }

        // Verificar limite de pedidos
        if ($planLimits['max_orders_per_month'] !== null) {
            if ($currentUsage['orders_this_month'] >= $planLimits['max_orders_per_month']) {
                $reachedLimits[] = 'orders';
                $messages[] = "Você atingiu o limite de {$planLimits['max_orders_per_month']} pedido(s) do mês.";
            }
        }

        $hasLimitReached = !empty($reachedLimits);
        $message = $hasLimitReached 
            ? 'Você atingiu o limite do seu plano atual. Para continuar usando sem restrições, considere migrar para um plano superior.'
            : 'Nenhum limite atingido.';

        return [
            'has_limit_reached' => $hasLimitReached,
            'reached_limits' => $reachedLimits,
            'current_usage' => $currentUsage,
            'plan_limits' => $planLimits,
            'plan_name' => $planName,
            'message' => $message,
            'detailed_messages' => $messages,
        ];
    }

    /**
     * Retorna o uso atual do tenant
     */
    public function getCurrentUsage(int $tenantId): array
    {
        // Contar usuários ativos
        $usersCount = $this->userRepository->countActiveUsersByTenant($tenantId);

        // Contar produtos ativos
        $productsCount = $this->productUsageRepository->countActiveProductsByTenant($tenantId);

        // Contar pedidos do mês atual
        $currentMonth = now()->startOfMonth();
        $currentMonthEnd = now()->endOfMonth();
        
        $ordersCount = $this->orderUsageRepository->countOrdersForTenantInPeriod(
            $tenantId,
            $currentMonth,
            $currentMonthEnd
        );

        return [
            'users' => $usersCount,
            'products' => $productsCount,
            'orders_this_month' => $ordersCount,
        ];
    }

    /**
     * Verifica se o limite de usuários foi atingido
     */
    public function checkUserLimit(int $tenantId): array
    {
        $tenant = $this->tenantRepository->getById($tenantId);
        
        if (!$tenant) {
            return ['reached' => false, 'current' => 0, 'max' => 0];
        }

        $tenant->loadMissing('plan');
        $plan = $tenant->plan;
        
        if (!$plan) {
            $maxUsers = 1;
        } else {
            $maxUsers = $plan->max_users;
            
            // Se null ou >= 999999, significa ilimitado
            if ($maxUsers === null || $maxUsers >= 999999) {
                return ['reached' => false, 'current' => 0, 'max' => null];
            }
        }

        $usersCount = $this->userRepository->countActiveUsersByTenant($tenantId);

        return [
            'reached' => $usersCount >= $maxUsers,
            'current' => $usersCount,
            'max' => $maxUsers,
        ];
    }

    /**
     * Verifica se o limite de pedidos foi atingido
     */
    public function checkOrderLimit(int $tenantId): array
    {
        $tenant = $this->tenantRepository->getById($tenantId);
        
        if (!$tenant) {
            return ['reached' => false, 'current' => 0, 'max' => 0];
        }

        $tenant->loadMissing('plan');
        $plan = $tenant->plan;
        
        if (!$plan) {
            $maxOrders = 30;
        } else {
            $maxOrders = $plan->max_orders_per_month;
            
            // Se null, significa ilimitado
            if ($maxOrders === null) {
                return ['reached' => false, 'current' => 0, 'max' => null];
            }
        }

        $currentMonth = now()->startOfMonth();
        $currentMonthEnd = now()->endOfMonth();
        
        $ordersCount = $this->orderUsageRepository->countOrdersForTenantInPeriod(
            $tenantId,
            $currentMonth,
            $currentMonthEnd
        );

        return [
            'reached' => $ordersCount >= $maxOrders,
            'current' => $ordersCount,
            'max' => $maxOrders,
        ];
    }

    /**
     * Verifica se o limite de produtos foi atingido
     */
    public function checkProductLimit(int $tenantId): array
    {
        $tenant = $this->tenantRepository->getById($tenantId);
        
        if (!$tenant) {
            return ['reached' => false, 'current' => 0, 'max' => 0];
        }

        $tenant->loadMissing('plan');
        $plan = $tenant->plan;
        
        if (!$plan) {
            $maxProducts = 50;
        } else {
            $maxProducts = $plan->max_products;
            
            // Se null ou >= 999999, significa ilimitado
            if ($maxProducts === null || $maxProducts >= 999999) {
                return ['reached' => false, 'current' => 0, 'max' => null];
            }
        }

        $productsCount = $this->productUsageRepository->countActiveProductsByTenant($tenantId);

        return [
            'reached' => $productsCount >= $maxProducts,
            'current' => $productsCount,
            'max' => $maxProducts,
        ];
    }
}
