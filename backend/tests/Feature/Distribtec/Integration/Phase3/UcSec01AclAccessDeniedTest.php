<?php

namespace Tests\Feature\Distribtec\Integration\Phase3;

use App\Models\Plan;
use App\Models\Tenant;
use App\Models\User;
use Database\Seeders\DistribtecPermissionSeeder;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;
use Tymon\JWTAuth\Facades\JWTAuth;

#[Group('integration')]
class UcSec01AclAccessDeniedTest extends TestCase
{
    #[Test]
    public function uc_sec01_denies_stock_movement_without_permission(): void
    {
        $plan   = Plan::factory()->create();
        $tenant = Tenant::factory()->accessible()->create(['plan_id' => $plan->id]);
        $user   = User::factory()->create(['tenant_id' => $tenant->id]);
        (new DistribtecPermissionSeeder())->run($tenant->id);

        $token = JWTAuth::fromUser($user);

        $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->postJson('/api/stock-movements', [
                'type'       => 'entrada',
                'product_id' => 1,
                'quantity'   => 1,
            ])
            ->assertStatus(403);
    }

    #[Test]
    public function uc_sec01_allows_stock_movement_with_permission(): void
    {
        $plan    = Plan::factory()->create();
        $tenant  = Tenant::factory()->accessible()->create(['plan_id' => $plan->id]);
        $user    = User::factory()->create(['tenant_id' => $tenant->id]);
        (new DistribtecPermissionSeeder())->run($tenant->id);

        $permission = \App\Models\Permission::where('slug', 'stock-movements.store')
            ->where('tenant_id', $tenant->id)
            ->first();
        $user->permissions()->attach($permission->id);

        $product   = \App\Models\Product::factory()->create(['tenant_id' => $tenant->id]);
        $warehouse = \App\Models\Warehouse::factory()->create(['tenant_id' => $tenant->id]);
        $token     = JWTAuth::fromUser($user);

        $this->withHeaders(['Authorization' => 'Bearer ' . $token])
            ->postJson('/api/stock-movements', [
                'type'         => 'entrada',
                'product_id'   => $product->id,
                'warehouse_id' => $warehouse->id,
                'quantity'     => 5,
                'batch_number' => 'ACL-OK',
            ])
            ->assertStatus(201);
    }
}
