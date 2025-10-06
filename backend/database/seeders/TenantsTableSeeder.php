<?php

namespace Database\Seeders;

use App\Models\Plan;
use App\Models\Tenant;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class TenantsTableSeeder extends Seeder
{

    public function run(): void
    {
        $plan = Plan::first();

        if (!$plan) {
            $this->command->error('❌ Nenhum plano encontrado. Execute PlansTableSeeder primeiro.');
            return;
        }

        $tenant = $plan->tenants()->firstOrCreate(
            ['name' => 'Empresa Dev'],
            [
                'cnpj' => '07768662000155',
                'name' => 'Empresa Dev',
                'url' => 'empresadev',
                'email' => 'empresadev@empresadev.com.br',
            ]
        );

        $this->command->info("✅ Tenant: " . ($tenant->wasRecentlyCreated ? 'criado' : 'já existe') . " - {$tenant->name}");
    }
}
