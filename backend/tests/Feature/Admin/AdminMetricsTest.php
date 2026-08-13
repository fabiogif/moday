<?php

namespace Tests\Feature\Admin;

use App\Models\AdminUser;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AdminMetricsTest extends TestCase
{
    use RefreshDatabase;

    private function actingAsAdmin()
    {
        $admin = AdminUser::factory()->create();
        $token = $admin->createToken('test-token')->plainTextToken;

        return $this->withHeaders([
            'Authorization' => 'Bearer ' . $token,
        ]);
    }

    #[Test]
    public function it_returns_revenue_metrics()
    {
        Tenant::factory()->create(['account_status' => 'active', 'mrr' => 149]);

        $response = $this->actingAsAdmin()
            ->getJson('/api/admin/metrics/revenue');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data',
            ]);
    }

    #[Test]
    public function it_returns_usage_metrics()
    {
        $response = $this->actingAsAdmin()
            ->getJson('/api/admin/metrics/usage');

        $response->assertStatus(200)
            ->assertJsonStructure(['success', 'data']);
    }

    #[Test]
    public function it_returns_messages_metrics()
    {
        $response = $this->actingAsAdmin()
            ->getJson('/api/admin/metrics/messages');

        $response->assertStatus(200)
            ->assertJsonStructure(['success', 'data']);
    }

    #[Test]
    public function it_returns_growth_metrics()
    {
        $response = $this->actingAsAdmin()
            ->getJson('/api/admin/metrics/growth');

        $response->assertStatus(200)
            ->assertJsonStructure(['success', 'data']);
    }

    #[Test]
    public function analyst_can_view_metrics()
    {
        $admin = AdminUser::factory()->analyst()->create();
        $token = $admin->createToken('test-token')->plainTextToken;

        $response = $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->getJson('/api/admin/metrics/growth');

        $response->assertStatus(200);
    }

    #[Test]
    public function it_denies_metrics_without_token()
    {
        $response = $this->getJson('/api/admin/metrics/revenue');

        $response->assertStatus(401);
    }
}
