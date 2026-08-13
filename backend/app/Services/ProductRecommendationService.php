<?php

namespace App\Services;

use App\Repositories\Contracts\OrderRepositoryInterface;
use App\Repositories\Contracts\ProductRepositoryInterface;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class ProductRecommendationService
{
    public function __construct(
        protected OrderRepositoryInterface $orderRepository,
        protected ProductRepositoryInterface $productRepository
    ) {
    }

    /**
     * Obter produtos frequentemente pedidos juntos
     * 
     * Analisa o histórico de pedidos para encontrar produtos que são
     * frequentemente pedidos juntos, otimizado com cache e queries eficientes
     */
    public function getFrequentlyBoughtTogether(int $tenantId, array $productIds, int $limit = 6): array
    {
        $sortedIds = $productIds;
        sort($sortedIds);
        $cacheKey = "frequently_bought_together_{$tenantId}_" . md5(implode(',', $sortedIds));
        
        return Cache::remember($cacheKey, 3600, function () use ($tenantId, $productIds, $limit) {
            if (empty($productIds)) {
                return [];
            }

            try {
                // Primeiro, buscar os IDs dos produtos pelos UUIDs usando o repository
                $productIdsInt = $this->productRepository->getProductIdsByUuids($tenantId, $productIds);

                if (empty($productIdsInt)) {
                    return [];
                }

                // Buscar produtos frequentemente pedidos juntos usando o repository
                $recommendations = $this->orderRepository->getFrequentlyBoughtTogether($tenantId, $productIdsInt, $limit);

                return $recommendations;
            } catch (\Exception $e) {
                Log::error('Erro ao calcular produtos frequentemente pedidos juntos', [
                    'error' => $e->getMessage(),
                    'tenant_id' => $tenantId,
                    'product_ids' => $productIds,
                ]);
                return [];
            }
        });
    }

    /**
     * Obter produtos mais vendidos
     */
    public function getBestSellers(int $tenantId, int $limit = 6, ?int $days = 30): array
    {
        $cacheKey = "best_sellers_{$tenantId}_{$days}_{$limit}";
        
        return Cache::remember($cacheKey, 1800, function () use ($tenantId, $limit, $days) {
            try {
                // Buscar produtos mais vendidos usando o repository
                $bestSellers = $this->orderRepository->getBestSellers($tenantId, $limit, $days);

                return $bestSellers;
            } catch (\Exception $e) {
                Log::error('Erro ao calcular produtos mais vendidos', [
                    'error' => $e->getMessage(),
                    'tenant_id' => $tenantId,
                ]);
                return [];
            }
        });
    }

    /**
     * Obter recomendações baseadas no carrinho atual
     * 
     * Combina análise de produtos frequentemente pedidos juntos
     * com produtos mais vendidos como fallback
     */
    public function getRecommendationsForCart(int $tenantId, array $cartProductIds, int $limit = 6): array
    {
        if (empty($cartProductIds)) {
            // Se o carrinho está vazio, retornar produtos mais vendidos
            return $this->getBestSellers($tenantId, $limit, 30);
        }

        // Buscar produtos frequentemente pedidos juntos
        $frequentlyBoughtTogether = $this->getFrequentlyBoughtTogether($tenantId, $cartProductIds, $limit);

        // Se não houver recomendações suficientes, completar com mais vendidos
        if (count($frequentlyBoughtTogether) < $limit) {
            $bestSellers = $this->getBestSellers($tenantId, $limit - count($frequentlyBoughtTogether), 30);
            
            // Remover duplicatas
            $existingUuids = array_column($frequentlyBoughtTogether, 'uuid');
            $bestSellers = array_filter($bestSellers, function ($product) use ($existingUuids, $cartProductIds) {
                return !in_array($product['uuid'], $existingUuids) && !in_array($product['uuid'], $cartProductIds);
            });

            $frequentlyBoughtTogether = array_merge($frequentlyBoughtTogether, array_values($bestSellers));
        }

        return array_slice($frequentlyBoughtTogether, 0, $limit);
    }

    /**
     * Limpar cache de recomendações
     */
    public function clearRecommendationCache(int $tenantId): void
    {
        Cache::tags(["recommendations_{$tenantId}"])->flush();
    }
}

