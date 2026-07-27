<?php

namespace Tests\Feature\Distribtec;

use App\Models\OfferRule;
use App\Models\Plan;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\User;
use PHPUnit\Framework\Attributes\Test;
use Tests\Feature\Distribtec\Traits\GrantsDistribtecPermissions;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

class OfferRuleApiTest extends TestCase
{
    use GrantsDistribtecPermissions;

    private User $user;
    private Tenant $tenant;
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        $plan         = Plan::factory()->create();
        $this->tenant = Tenant::factory()->accessible()->create(['plan_id' => $plan->id]);
        $this->user   = User::factory()->create(['tenant_id' => $this->tenant->id]);
        $this->grantDistribtecPermissions($this->user, $this->tenant->id);
        $this->token  = JWTAuth::fromUser($this->user);
    }

    private function auth(): array
    {
        return ['Authorization' => 'Bearer ' . $this->token];
    }

    private function product(array $attributes = []): Product
    {
        return Product::factory()->create(array_merge(['tenant_id' => $this->tenant->id], $attributes));
    }

    // -------------------------------------------------------------------------
    // CRUD
    // -------------------------------------------------------------------------

    #[Test]
    public function guest_cannot_manage_offer_rules(): void
    {
        $this->getJson('/api/offer-rules')->assertStatus(401);
    }

    #[Test]
    public function it_creates_a_quantity_discount_rule(): void
    {
        $product = $this->product();

        $payload = [
            'name'             => 'Acima de 10 unidades',
            'type'             => 'quantity_discount',
            'discount_percent' => 15,
            'products' => [
                ['product_id' => $product->id, 'role' => 'trigger', 'min_quantity' => 10],
            ],
        ];

        $this->withHeaders($this->auth())
            ->postJson('/api/offer-rules', $payload)
            ->assertStatus(201)
            ->assertJsonPath('data.name', 'Acima de 10 unidades');

        $this->assertDatabaseHas('offer_rules', ['tenant_id' => $this->tenant->id, 'type' => 'quantity_discount']);
        $this->assertDatabaseHas('offer_rule_products', ['product_id' => $product->id, 'role' => 'trigger', 'min_quantity' => 10]);
    }

    #[Test]
    public function it_rejects_quantity_discount_without_discount_percent(): void
    {
        $product = $this->product();

        $payload = [
            'name'    => 'Sem desconto',
            'type'    => 'quantity_discount',
            'products' => [
                ['product_id' => $product->id, 'role' => 'trigger', 'min_quantity' => 10],
            ],
        ];

        $this->withHeaders($this->auth())
            ->postJson('/api/offer-rules', $payload)
            ->assertStatus(422);
    }

    #[Test]
    public function it_rejects_combo_with_a_single_trigger_product(): void
    {
        $product = $this->product();

        $payload = [
            'name'             => 'Combo inválido',
            'type'             => 'combo',
            'discount_percent' => 10,
            'products' => [
                ['product_id' => $product->id, 'role' => 'trigger', 'min_quantity' => 1],
            ],
        ];

        $this->withHeaders($this->auth())
            ->postJson('/api/offer-rules', $payload)
            ->assertStatus(422);
    }

    #[Test]
    public function it_updates_and_deletes_a_rule(): void
    {
        $product = $this->product();
        $rule = OfferRule::create([
            'tenant_id'        => $this->tenant->id,
            'name'             => 'Original',
            'type'             => 'quantity_discount',
            'discount_percent' => 10,
            'is_active'        => true,
        ]);
        $rule->products()->create(['product_id' => $product->id, 'role' => 'trigger', 'min_quantity' => 5]);

        $this->withHeaders($this->auth())
            ->putJson("/api/offer-rules/{$rule->id}", [
                'name'             => 'Atualizado',
                'type'             => 'quantity_discount',
                'discount_percent' => 20,
                'is_active'        => false,
                'products' => [
                    ['product_id' => $product->id, 'role' => 'trigger', 'min_quantity' => 8],
                ],
            ])
            ->assertStatus(200)
            ->assertJsonPath('data.name', 'Atualizado')
            ->assertJsonPath('data.is_active', false);

        $this->assertDatabaseHas('offer_rule_products', ['offer_rule_id' => $rule->id, 'min_quantity' => 8]);

        $this->withHeaders($this->auth())
            ->deleteJson("/api/offer-rules/{$rule->id}")
            ->assertStatus(200);

        $this->assertDatabaseMissing('offer_rules', ['id' => $rule->id]);
    }

    #[Test]
    public function it_does_not_show_offer_rules_from_another_tenant(): void
    {
        $otherPlan   = Plan::factory()->create();
        $otherTenant = Tenant::factory()->accessible()->create(['plan_id' => $otherPlan->id]);
        $otherRule   = OfferRule::create([
            'tenant_id' => $otherTenant->id,
            'name'      => 'De outro tenant',
            'type'      => 'quantity_discount',
            'discount_percent' => 10,
        ]);

        $this->withHeaders($this->auth())
            ->getJson("/api/offer-rules/{$otherRule->id}")
            ->assertStatus(404);
    }

    // -------------------------------------------------------------------------
    // Evaluate (preview)
    // -------------------------------------------------------------------------

    #[Test]
    public function evaluate_endpoint_returns_discounts_and_suggestions(): void
    {
        $trigger = $this->product();
        $suggested = $this->product();

        $rule = OfferRule::create([
            'tenant_id' => $this->tenant->id,
            'name'      => 'Sugestão automática',
            'type'      => 'cross_sell',
            'is_active' => true,
        ]);
        $rule->products()->create(['product_id' => $trigger->id, 'role' => 'trigger', 'min_quantity' => 1]);
        $rule->products()->create(['product_id' => $suggested->id, 'role' => 'result', 'min_quantity' => 1]);

        $response = $this->withHeaders($this->auth())
            ->postJson('/api/sale-orders/offers/evaluate', [
                'items' => [
                    ['product_id' => $trigger->id, 'quantity' => 1],
                ],
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.suggestions.0.product_id', $suggested->id);
    }

    // -------------------------------------------------------------------------
    // Integration with sale order creation
    // -------------------------------------------------------------------------

    #[Test]
    public function creating_a_sale_order_applies_quantity_discount_automatically(): void
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

        $payload = [
            'status' => 'orcamento',
            'items'  => [
                ['product_id' => $product->id, 'quantity' => 10, 'unit_price' => 100, 'discount_percent' => 0],
            ],
        ];

        $this->withHeaders($this->auth())
            ->postJson('/api/sale-orders', $payload)
            ->assertStatus(201);

        $this->assertDatabaseHas('sale_order_items', [
            'product_id'       => $product->id,
            'discount_percent' => 15,
            'offer_rule_id'    => $rule->id,
        ]);
    }

    #[Test]
    public function creating_a_sale_order_keeps_offer_rule_when_preview_discount_is_resent(): void
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

        $payload = [
            'status' => 'orcamento',
            'items'  => [
                // Cliente reenvia o % já mostrado no preview (bug clássico do badge/auditoria)
                ['product_id' => $product->id, 'quantity' => 10, 'unit_price' => 100, 'discount_percent' => 15],
            ],
        ];

        $this->withHeaders($this->auth())
            ->postJson('/api/sale-orders', $payload)
            ->assertStatus(201);

        $this->assertDatabaseHas('sale_order_items', [
            'product_id'       => $product->id,
            'discount_percent' => 15,
            'offer_rule_id'    => $rule->id,
        ]);
    }
}
