<?php

namespace Database\Seeders;

use App\Models\Client;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ClientSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = \App\Models\Tenant::first();

        $clients = [
            [
                'name' => 'Farmácia Central LTDA',
                'cnpj' => '12.345.678/0001-90',
                'company_name' => 'Farmácia Central LTDA',
                'trade_name' => 'Farmácia Central',
                'client_type' => 'farmacia',
                'email' => 'compras@farmaciacentral.com.br',
                'phone' => '(11) 3333-4444',
                'credit_limit' => 50000.00,
                'payment_term_days' => 30,
                'city' => 'São Paulo', 'state' => 'SP',
            ],
            [
                'name' => 'Drogaria Popular EIRELI',
                'cnpj' => '98.765.432/0001-10',
                'company_name' => 'Drogaria Popular EIRELI',
                'trade_name' => 'Drogaria Popular',
                'client_type' => 'drogaria',
                'email' => 'pedidos@drogariapopular.com.br',
                'phone' => '(21) 2222-5555',
                'credit_limit' => 30000.00,
                'payment_term_days' => 60,
                'city' => 'Rio de Janeiro', 'state' => 'RJ',
            ],
            [
                'name' => 'Supermercado Família S/A',
                'cnpj' => '45.678.901/0001-23',
                'company_name' => 'Supermercado Família S/A',
                'trade_name' => 'Super Família',
                'client_type' => 'supermercado',
                'email' => 'compras@superfamilia.com.br',
                'phone' => '(31) 4444-6666',
                'credit_limit' => 100000.00,
                'payment_term_days' => 30,
                'city' => 'Belo Horizonte', 'state' => 'MG',
            ],
            [
                'name' => 'Hospital São Lucas LTDA',
                'cnpj' => '11.222.333/0001-44',
                'company_name' => 'Hospital São Lucas LTDA',
                'trade_name' => 'HSL',
                'client_type' => 'hospital',
                'email' => 'suprimentos@hsl.com.br',
                'phone' => '(41) 5555-7777',
                'credit_limit' => 200000.00,
                'payment_term_days' => 90,
                'city' => 'Curitiba', 'state' => 'PR',
            ],
        ];

        foreach ($clients as $data) {
            Client::firstOrCreate(
                ['cnpj' => $data['cnpj'], 'tenant_id' => $tenant->id],
                array_merge($data, [
                    'uuid' => Str::uuid(),
                    'tenant_id' => $tenant->id,
                    'is_active' => true,
                    'password' => Hash::make('password'),
                ])
            );
        }

        $this->command->info('✅ Clientes B2B criados.');
    }
}
