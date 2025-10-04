<?php

namespace Database\Seeders;

use App\Models\Client;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ClientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Cliente administrador
        Client::create([
            'name' => 'João Silva',
            'cpf' => '123.456.789-00',
            'email' => 'joao@example.com',
            'phone' => '(11) 99999-9999',
            'password' => Hash::make('password'),
            'uuid' => Str::uuid(),
            'address' => 'Rua das Flores, 123',
            'city' => 'São Paulo',
            'state' => 'SP',
            'zip_code' => '01234-567',
            'neighborhood' => 'Centro',
            'number' => '123',
            'complement' => 'Apto 101',
            'is_active' => true,
            'tenant_id' => 1,
        ]);

        // Cliente com endereço completo
        Client::create([
            'name' => 'Maria Santos',
            'cpf' => '987.654.321-00',
            'email' => 'maria@example.com',
            'phone' => '(11) 88888-8888',
            'password' => Hash::make('password'),
            'uuid' => Str::uuid(),
            'address' => 'Avenida Paulista, 1000',
            'city' => 'São Paulo',
            'state' => 'SP',
            'zip_code' => '01310-100',
            'neighborhood' => 'Bela Vista',
            'number' => '1000',
            'complement' => 'Sala 501',
            'is_active' => true,
            'tenant_id' => 1,
        ]);

        // Cliente sem endereço
        Client::create([
            'name' => 'Pedro Costa',
            'cpf' => '456.789.123-00',
            'email' => 'pedro@example.com',
            'phone' => '(11) 77777-7777',
            'password' => Hash::make('password'),
            'uuid' => Str::uuid(),
            'is_active' => true,
            'tenant_id' => 1,
        ]);

        // Cliente inativo
        Client::create([
            'name' => 'Ana Oliveira',
            'cpf' => '789.123.456-00',
            'email' => 'ana@example.com',
            'phone' => '(11) 66666-6666',
            'password' => Hash::make('password'),
            'uuid' => Str::uuid(),
            'address' => 'Rua da Consolação, 500',
            'city' => 'São Paulo',
            'state' => 'SP',
            'zip_code' => '01302-000',
            'neighborhood' => 'Consolação',
            'number' => '500',
            'is_active' => false,
            'tenant_id' => 1,
        ]);

        // Gerar clientes aleatórios
        Client::factory(20)->create();
    }
}
