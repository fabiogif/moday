<?php

namespace Database\Factories;

use App\Models\PlanMigration;
use App\Models\Tenant;
use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\PlanMigration>
 */
class PlanMigrationFactory extends Factory
{
    protected $model = PlanMigration::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'from_plan_id' => Plan::factory(),
            'to_plan_id' => Plan::factory(),
            'status' => 'completed',
            'migrated_at' => now(),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
