<?php

namespace App\Services;

use App\Models\Tenant;
use App\Repositories\Contracts\ProductRepositoryInterface;

class PublicOrderCalculationService
{
    public function __construct(
        private readonly ProductRepositoryInterface $productRepository,
    ) {}
    /**
     * Calculate order total and prepare products data
     */
    public function calculateOrder(array $products, Tenant $tenant): array
    {
        $total = 0;
        $orderProducts = [];

        foreach ($products as $item) {
            $product = $this->productRepository->getByUuid($item['uuid']);

            if (!$product) {
                throw new \Exception("Produto não encontrado: {$item['uuid']}");
            }

            $basePrice = $product->promotional_price ?? $product->price;
            $optionalsTotal = 0;

            if (!empty($item['optionals']) && is_array($item['optionals'])) {
                foreach ($item['optionals'] as $optional) {
                    if (!isset($optional['price'])) {
                        continue;
                    }

                    $optionalPrice = (float) $optional['price'];
                    $optionalQty = isset($optional['quantity']) ? (int) $optional['quantity'] : 1;

                    $optionalsTotal += $optionalPrice * $optionalQty;
                }
            }

            $unitPrice = $basePrice + $optionalsTotal;
            $itemTotal = $unitPrice * $item['quantity'];
            $total += $itemTotal;

            $orderProducts[] = [
                'product_id' => $product->id,
                'quantity' => $item['quantity'],
                'price' => $unitPrice,
            ];
        }

        return [
            'total' => $total,
            'products' => $orderProducts,
        ];
    }
}
