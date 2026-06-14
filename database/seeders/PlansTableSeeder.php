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
                'name' => 'Plano Básico',
                'price' => 199,
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
                'name' => 'Plano Profissional',
                'price' => 399,
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
                'name' => 'Plano Enterprise',
                'price' => 799,
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
            $url = Str::slug($data['name']);

            Plan::updateOrCreate(
                ['url' => $url],
                array_merge($data, ['url' => $url])
            );
        }
    }
}
