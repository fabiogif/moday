<?php

namespace Tests\Unit\Services\Audit;

use App\Models\DiscountApproval;
use App\Models\Profile;
use App\Models\SaleOrder;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Audit\DiscountApprovalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DiscountApprovalServiceTest extends TestCase
{
    use RefreshDatabase;

    private DiscountApprovalService $service;
    private int $tenantId;
    private User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service  = app(DiscountApprovalService::class);
        $tenant         = Tenant::factory()->create();
        $this->tenantId = $tenant->id;
        $this->user     = User::factory()->create(['tenant_id' => $this->tenantId]);
    }

    public function test_evaluate_discount_returns_null_when_within_limit(): void
    {
        $profile = Profile::factory()->create([
            'tenant_id'           => $this->tenantId,
            'max_discount_percent'=> 20,
        ]);
        $this->user->update(['profile_id' => $profile->id]);

        $order = SaleOrder::factory()->create([
            'tenant_id'       => $this->tenantId,
            'subtotal'        => 1000,
            'discount_amount' => 150, // 15% — within 20% limit
        ]);

        $result = $this->service->evaluateDiscount($order, $this->user);

        $this->assertNull($result);
    }

    public function test_evaluate_discount_creates_approval_when_exceeds_limit(): void
    {
        $profile = Profile::factory()->create([
            'tenant_id'           => $this->tenantId,
            'max_discount_percent'=> 10,
        ]);
        $this->user->update(['profile_id' => $profile->id]);

        $order = SaleOrder::factory()->create([
            'tenant_id'       => $this->tenantId,
            'subtotal'        => 1000,
            'discount_amount' => 250, // 25% — exceeds 10% limit
        ]);

        $approval = $this->service->evaluateDiscount($order, $this->user);

        $this->assertNotNull($approval);
        $this->assertEquals('pending', $approval->status);
        $this->assertEquals(25.0, (float) $approval->requested_discount_pct);
    }

    public function test_approve_changes_status_to_approved(): void
    {
        $manager  = User::factory()->create(['tenant_id' => $this->tenantId]);
        $order    = SaleOrder::factory()->create(['tenant_id' => $this->tenantId, 'subtotal' => 1000, 'discount_amount' => 300]);

        $approval = DiscountApproval::create([
            'tenant_id'              => $this->tenantId,
            'sale_order_id'          => $order->id,
            'requested_by'           => $this->user->id,
            'requested_discount_pct' => 30,
            'profile_max_pct'        => 10,
            'status'                 => 'pending',
        ]);

        $result = $this->service->approve($this->tenantId, $approval->id, $manager, 'Autorizado por gerente');

        $this->assertEquals('approved', $result->status);
        $this->assertEquals($manager->id, $result->approved_by);
        $this->assertEquals('Autorizado por gerente', $result->notes);
    }

    public function test_reject_changes_status_to_rejected(): void
    {
        $manager = User::factory()->create(['tenant_id' => $this->tenantId]);
        $order   = SaleOrder::factory()->create(['tenant_id' => $this->tenantId, 'subtotal' => 1000, 'discount_amount' => 300]);

        $approval = DiscountApproval::create([
            'tenant_id'              => $this->tenantId,
            'sale_order_id'          => $order->id,
            'requested_by'           => $this->user->id,
            'requested_discount_pct' => 30,
            'profile_max_pct'        => 10,
            'status'                 => 'pending',
        ]);

        $result = $this->service->reject($this->tenantId, $approval->id, $manager, 'Acima da política');

        $this->assertEquals('rejected', $result->status);
        $this->assertNotNull($result->resolved_at);
    }

    public function test_approve_throws_when_already_resolved(): void
    {
        $manager = User::factory()->create(['tenant_id' => $this->tenantId]);
        $order   = SaleOrder::factory()->create(['tenant_id' => $this->tenantId, 'subtotal' => 1000, 'discount_amount' => 200]);

        $approval = DiscountApproval::create([
            'tenant_id'              => $this->tenantId,
            'sale_order_id'          => $order->id,
            'requested_by'           => $this->user->id,
            'requested_discount_pct' => 20,
            'profile_max_pct'        => 5,
            'status'                 => 'approved',
            'resolved_at'            => now(),
        ]);

        $this->expectException(\DomainException::class);
        $this->service->approve($this->tenantId, $approval->id, $manager);
    }

    public function test_pending_count_returns_correct_number(): void
    {
        $order = SaleOrder::factory()->create(['tenant_id' => $this->tenantId, 'subtotal' => 1000, 'discount_amount' => 200]);

        DiscountApproval::create(['tenant_id' => $this->tenantId, 'sale_order_id' => $order->id, 'requested_by' => $this->user->id, 'requested_discount_pct' => 20, 'profile_max_pct' => 10, 'status' => 'pending']);
        DiscountApproval::create(['tenant_id' => $this->tenantId, 'sale_order_id' => $order->id, 'requested_by' => $this->user->id, 'requested_discount_pct' => 20, 'profile_max_pct' => 10, 'status' => 'approved', 'resolved_at' => now()]);

        $this->assertEquals(1, $this->service->pendingCount($this->tenantId));
    }
}
