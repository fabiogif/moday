<?php

namespace Database\Factories;

use App\Models\AdminActionLog;
use App\Models\AdminUser;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AdminActionLog>
 */
class AdminActionLogFactory extends Factory
{
    protected $model = AdminActionLog::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $actions = [
            'tenant.activate',
            'tenant.suspend',
            'tenant.block',
            'tenant.unblock',
            'tenant.update',
            'tenant.update_plan',
        ];

        return [
            'admin_user_id' => AdminUser::factory(),
            'tenant_id' => Tenant::factory(),
            'action' => fake()->randomElement($actions),
            'description' => fake()->sentence(),
            'old_values' => ['status' => 'trial'],
            'new_values' => ['status' => 'active'],
            'ip_address' => fake()->ipv4(),
        ];
    }
}

