<?php

namespace Database\Factories;

use App\Models\TenantMetrics;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TenantMetrics>
 */
class TenantMetricsFactory extends Factory
{
    protected $model = TenantMetrics::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'metric_date' => now()->subDays(rand(0, 30)),
            'total_users' => rand(1, 20),
            'active_users' => rand(1, 15),
            'total_orders' => rand(0, 50),
            'total_revenue' => rand(0, 5000),
            'messages_sent' => rand(0, 1000),
            'messages_whatsapp' => rand(0, 800),
            'messages_email' => rand(0, 150),
            'messages_sms' => rand(0, 50),
            'login_count' => rand(5, 100),
        ];
    }
}

