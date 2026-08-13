<?php

namespace Tests\Unit\Services;

use App\Models\OfferRule;
use App\Models\Plan;
use App\Models\Product;
use App\Models\Tenant;
use App\Services\Sale\OfferEngineService;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class OfferEngineServiceTest extends TestCase
{
    private Tenant $tenant;
    private OfferEngineService $service;

    protected function setUp(): void
    {
        parent::setUp();

        $plan         = Plan::factory()->create();
        $this->tenant = Tenant::factory()->create(['plan_id' => $plan->id]);
        $this->service = app(OfferEngineService::class);
    }

    private function product(array $attributes = []): Product
    {
        return Product::factory()->create(array_merge(['tenant_id' => $this->tenant->id], $attributes));
    }

    #[Test]
    public function it_applies_quantity_discount_when_threshold_is_met(): void
    {
        $product = $this->product(['price' => 100]);

        $rule = OfferRule::create([
            'tenant_id'        => $this->tenant->id,
            'name'             => 'Acima de 10 unidades',
            'type'             => 'quantity_discount',
            'discount_percent' => 15,
            'is_active'        => true,
            'priority'         => 0,
        ]);
        $rule->products()->create(['product_id' => $product->id, 'role' => 'trigger', 'min_quantity' => 10]);

        $result = $this->service->evaluate($this->tenant->id, [
            ['product_id' => $product->id, 'quantity' => 10, 'unit_price' => 100, 'discount_percent' => 0],
        ]);

        $this->assertSame(15.0, (float) $result['items'][0]['discount_percent']);
        $this->assertSame($rule->id, $result['items'][0]['offer_rule_id']);
        $this->assertEmpty($result['suggestions']);
    }

    #[Test]
    public function it_does_not_apply_quantity_discount_below_threshold(): void
    {
        $product = $this->product(['price' => 100]);

        $rule = OfferRule::create([
            'tenant_id'        => $this->tenant->id,
            'name'             => 'Acima de 10 unidades',
            'type'             => 'quantity_discount',
            'discount_percent' => 15,
            'is_active'        => true,
        ]);
        $rule->products()->create(['product_id' => $product->id, 'role' => 'trigger', 'min_quantity' => 10]);

        $result = $this->service->evaluate($this->tenant->id, [
            ['product_id' => $product->id, 'quantity' => 5, 'unit_price' => 100, 'discount_percent' => 0],
        ]);

        $this->assertSame(0, (int) ($result['items'][0]['discount_percent'] ?? 0));
        $this->assertArrayNotHasKey('offer_rule_id', $result['items'][0]);
    }

    #[Test]
    public function it_does_not_override_a_bigger_manual_discount(): void
    {
        $product = $this->product(['price' => 100]);

        $rule = OfferRule::create([
            'tenant_id'        => $this->tenant->id,
            'name'             => 'Acima de 10 unidades',
            'type'             => 'quantity_discount',
            'discount_percent' => 15,
            'is_active'        => true,
        ]);
        $rule->products()->create(['product_id' => $product->id, 'role' => 'trigger', 'min_quantity' => 10]);

        $result = $this->service->evaluate($this->tenant->id, [
            ['product_id' => $product->id, 'quantity' => 10, 'unit_price' => 100, 'discount_percent' => 30],
        ]);

        $this->assertSame(30, (int) $result['items'][0]['discount_percent']);
        $this->assertArrayNotHasKey('offer_rule_id', $result['items'][0]);
    }

    #[Test]
    public function it_confirms_offer_rule_when_client_already_sent_the_same_discount_percent(): void
    {
        $product = $this->product(['price' => 100]);

        $rule = OfferRule::create([
            'tenant_id'        => $this->tenant->id,
            'name'             => 'Acima de 10 unidades',
            'type'             => 'quantity_discount',
            'discount_percent' => 15,
            'is_active'        => true,
        ]);
        $rule->products()->create(['product_id' => $product->id, 'role' => 'trigger', 'min_quantity' => 10]);

        // Simula payload do wizard/mobile após o preview ter aplicado 15%
        $result = $this->service->evaluate($this->tenant->id, [
            ['product_id' => $product->id, 'quantity' => 10, 'unit_price' => 100, 'discount_percent' => 15],
        ]);

        $this->assertSame(15, (int) $result['items'][0]['discount_percent']);
        $this->assertSame($rule->id, $result['items'][0]['offer_rule_id']);
    }

    #[Test]
    public function it_applies_combo_discount_when_all_trigger_products_are_present(): void
    {
        $productA = $this->product(['price' => 50]);
        $productB = $this->product(['price' => 80]);

        $rule = OfferRule::create([
            'tenant_id'        => $this->tenant->id,
            'name'             => 'Combo A+B',
            'type'             => 'combo',
            'discount_percent' => 10,
            'is_active'        => true,
        ]);
        $rule->products()->create(['product_id' => $productA->id, 'role' => 'trigger', 'min_quantity' => 1]);
        $rule->products()->create(['product_id' => $productB->id, 'role' => 'trigger', 'min_quantity' => 1]);

        $result = $this->service->evaluate($this->tenant->id, [
            ['product_id' => $productA->id, 'quantity' => 1, 'unit_price' => 50, 'discount_percent' => 0],
            ['product_id' => $productB->id, 'quantity' => 1, 'unit_price' => 80, 'discount_percent' => 0],
        ]);

        $this->assertSame(10.0, (float) $result['items'][0]['discount_percent']);
        $this->assertSame(10.0, (float) $result['items'][1]['discount_percent']);
    }

    #[Test]
    public function it_does_not_apply_combo_discount_when_missing_one_product(): void
    {
        $productA = $this->product(['price' => 50]);
        $productB = $this->product(['price' => 80]);

        $rule = OfferRule::create([
            'tenant_id'        => $this->tenant->id,
            'name'             => 'Combo A+B',
            'type'             => 'combo',
            'discount_percent' => 10,
            'is_active'        => true,
        ]);
        $rule->products()->create(['product_id' => $productA->id, 'role' => 'trigger', 'min_quantity' => 1]);
        $rule->products()->create(['product_id' => $productB->id, 'role' => 'trigger', 'min_quantity' => 1]);

        $result = $this->service->evaluate($this->tenant->id, [
            ['product_id' => $productA->id, 'quantity' => 1, 'unit_price' => 50, 'discount_percent' => 0],
        ]);

        $this->assertSame(0, (int) ($result['items'][0]['discount_percent'] ?? 0));
    }

    #[Test]
    public function it_suggests_cross_sell_product_when_trigger_present_and_result_absent(): void
    {
        $trigger = $this->product(['name' => 'Furadeira']);
        $result  = $this->product(['name' => 'Kit de brocas']);

        $rule = OfferRule::create([
            'tenant_id' => $this->tenant->id,
            'name'      => 'Quem compra furadeira leva brocas',
            'type'      => 'cross_sell',
            'is_active' => true,
        ]);
        $rule->products()->create(['product_id' => $trigger->id, 'role' => 'trigger', 'min_quantity' => 1]);
        $rule->products()->create(['product_id' => $result->id, 'role' => 'result', 'min_quantity' => 1]);

        $evaluation = $this->service->evaluate($this->tenant->id, [
            ['product_id' => $trigger->id, 'quantity' => 1, 'unit_price' => 200, 'discount_percent' => 0],
        ]);

        $this->assertCount(1, $evaluation['suggestions']);
        $this->assertSame($result->id, $evaluation['suggestions'][0]['product_id']);
        $this->assertSame('Kit de brocas', $evaluation['suggestions'][0]['product_name']);
    }

    #[Test]
    public function it_does_not_suggest_cross_sell_product_already_in_cart(): void
    {
        $trigger = $this->product();
        $result  = $this->product();

        $rule = OfferRule::create([
            'tenant_id' => $this->tenant->id,
            'name'      => 'Sugestão',
            'type'      => 'cross_sell',
            'is_active' => true,
        ]);
        $rule->products()->create(['product_id' => $trigger->id, 'role' => 'trigger', 'min_quantity' => 1]);
        $rule->products()->create(['product_id' => $result->id, 'role' => 'result', 'min_quantity' => 1]);

        $evaluation = $this->service->evaluate($this->tenant->id, [
            ['product_id' => $trigger->id, 'quantity' => 1, 'unit_price' => 200, 'discount_percent' => 0],
            ['product_id' => $result->id, 'quantity' => 1, 'unit_price' => 50, 'discount_percent' => 0],
        ]);

        $this->assertEmpty($evaluation['suggestions']);
    }

    #[Test]
    public function it_ignores_inactive_rules(): void
    {
        $product = $this->product(['price' => 100]);

        $rule = OfferRule::create([
            'tenant_id'        => $this->tenant->id,
            'name'             => 'Regra desativada',
            'type'             => 'quantity_discount',
            'discount_percent' => 15,
            'is_active'        => false,
        ]);
        $rule->products()->create(['product_id' => $product->id, 'role' => 'trigger', 'min_quantity' => 1]);

        $result = $this->service->evaluate($this->tenant->id, [
            ['product_id' => $product->id, 'quantity' => 5, 'unit_price' => 100, 'discount_percent' => 0],
        ]);

        $this->assertSame(0, (int) ($result['items'][0]['discount_percent'] ?? 0));
    }

    #[Test]
    public function it_keeps_the_highest_discount_when_multiple_rules_match_the_same_product(): void
    {
        $product = $this->product(['price' => 100]);

        $lowerRule = OfferRule::create([
            'tenant_id'        => $this->tenant->id,
            'name'             => 'Desconto menor',
            'type'             => 'quantity_discount',
            'discount_percent' => 5,
            'is_active'        => true,
            'priority'         => 10,
        ]);
        $lowerRule->products()->create(['product_id' => $product->id, 'role' => 'trigger', 'min_quantity' => 1]);

        $higherRule = OfferRule::create([
            'tenant_id'        => $this->tenant->id,
            'name'             => 'Desconto maior',
            'type'             => 'quantity_discount',
            'discount_percent' => 20,
            'is_active'        => true,
            'priority'         => 0,
        ]);
        $higherRule->products()->create(['product_id' => $product->id, 'role' => 'trigger', 'min_quantity' => 1]);

        $result = $this->service->evaluate($this->tenant->id, [
            ['product_id' => $product->id, 'quantity' => 1, 'unit_price' => 100, 'discount_percent' => 0],
        ]);

        $this->assertSame(20.0, (float) $result['items'][0]['discount_percent']);
        $this->assertSame($higherRule->id, $result['items'][0]['offer_rule_id']);
    }
}
