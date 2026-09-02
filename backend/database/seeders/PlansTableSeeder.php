<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PlansTableSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'legacy_url' => 'plano-basico',
                'name' => 'Sabor Start',
                'price' => 39.90,
                'description' => 'Até 3 usuários, módulos essenciais',
                'is_active' => true,
                'max_users' => 3,
                'max_products' => 500,
                'max_orders_per_month' => 500,
                'has_marketing' => true,
                'has_order_completion_email' => true,
                'has_reports' => true,
            ],
            [
                'legacy_url' => 'plano-profissional',
                'name' => 'Sabor Pro',
                'price' => 59.90,
                'description' => 'Até 10 usuários, todos os módulos',
                'is_active' => true,
                'max_users' => 10,
                'max_products' => 5000,
                'max_orders_per_month' => 5000,
                'has_marketing' => true,
                'has_order_completion_email' => true,
                'has_reports' => true,
            ],
            [
                'legacy_url' => 'plano-enterprise',
                'name' => 'Sabor Chef',
                'price' => 79.90,
                'description' => 'Usuários ilimitados, suporte dedicado',
                'is_active' => true,
                'max_users' => 999999,
                'max_products' => 999999,
                'max_orders_per_month' => null,
                'has_marketing' => true,
                'has_order_completion_email' => true,
                'has_reports' => true,
            ],
        ];

        foreach ($plans as $data) {
            $legacyUrl = $data['legacy_url'] ?? null;
            $url = Str::slug($data['name']);
            unset($data['legacy_url']);

            $plan = $legacyUrl ? Plan::where('url', $legacyUrl)->first() : null;
            $plan ??= Plan::where('url', $url)->first();

            if ($plan) {
                $plan->update(array_merge($data, ['url' => $url]));
            } else {
                Plan::create(array_merge($data, ['url' => $url]));
            }
        }
    }
}
