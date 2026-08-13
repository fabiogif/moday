<?php

namespace Tests\Unit\Rules;

use App\Models\Product;
use App\Rules\Product\ProductCatalogVisibilityRule;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductCatalogVisibilityRuleTest extends TestCase
{
    use RefreshDatabase;

    public function test_passes_only_for_active_products_with_stock(): void
    {
        $activeInStock = Product::factory()->create([
            'is_active' => true,
            'qtd_stock' => 5,
        ]);

        $inactive = Product::factory()->create([
            'is_active' => false,
            'qtd_stock' => 10,
        ]);

        $outOfStock = Product::factory()->create([
            'is_active' => true,
            'qtd_stock' => 0,
        ]);

        $this->assertTrue(ProductCatalogVisibilityRule::passes($activeInStock));
        $this->assertFalse(ProductCatalogVisibilityRule::passes($inactive));
        $this->assertFalse(ProductCatalogVisibilityRule::passes($outOfStock));
    }

    public function test_apply_filters_query(): void
    {
        $tenantId = Product::factory()->create(['is_active' => true, 'qtd_stock' => 1])->tenant_id;

        Product::factory()->create(['tenant_id' => $tenantId, 'is_active' => true, 'qtd_stock' => 3]);
        Product::factory()->create(['tenant_id' => $tenantId, 'is_active' => false, 'qtd_stock' => 3]);
        Product::factory()->create(['tenant_id' => $tenantId, 'is_active' => true, 'qtd_stock' => 0]);

        $visible = Product::query()
            ->where('tenant_id', $tenantId)
            ->visibleInCatalog()
            ->pluck('id')
            ->all();

        $this->assertCount(2, $visible);
    }
}
