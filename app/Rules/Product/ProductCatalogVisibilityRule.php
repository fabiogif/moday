<?php

namespace App\Rules\Product;

use App\Models\Product;
use Illuminate\Database\Eloquent\Builder;

/**
 * Regra de negócio: produtos exibidos no cardápio público e no PDV.
 *
 * Devem estar ativos e com estoque disponível (qtd_stock > 0).
 * A gestão de produtos (/api/product) lista todos os itens do tenant.
 */
final class ProductCatalogVisibilityRule
{
    public static function apply(Builder $query): Builder
    {
        return $query
            ->where($query->getModel()->getTable().'.is_active', true)
            ->where($query->getModel()->getTable().'.qtd_stock', '>', 0);
    }

    public static function passes(Product $product): bool
    {
        return $product->is_active && (int) $product->qtd_stock > 0;
    }
}
