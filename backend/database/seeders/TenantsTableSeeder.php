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


        $plan->tenants()->create([
            'cnpj' => '07768662000155',
            'name' => 'Empresa Dev',
            'url' => 'empresadev',
            'email' => 'empresadev@empresadev.com.br',
        ]);


    }
}
