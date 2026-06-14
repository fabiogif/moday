<?php

namespace Database\Seeders;

use App\Models\Profile;
use App\Models\Tenant;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ProfileSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Buscar tenant padrão
        $tenant = Tenant::first();
        if (!$tenant) {
            $this->command->error('❌ Nenhum tenant encontrado. Execute TenantsTableSeeder primeiro.');
            return;
        }

        $profiles = [
            [
                'name' => 'Super Admin',
                'description' => 'Acesso total ao sistema',
                'is_active' => true,
                'tenant_id' => $tenant->id,
            ],
            [
                'name' => 'Administrador',
                'description' => 'Administrador do sistema',
                'is_active' => true,
                'tenant_id' => $tenant->id,
            ],
            [
                'name' => 'Gerente',
                'description' => 'Gerente do restaurante',
                'is_active' => true,
                'tenant_id' => $tenant->id,
            ],
            [
                'name' => 'Garçom',
                'description' => 'Garçom do restaurante',
                'is_active' => true,
                'tenant_id' => $tenant->id,
            ],
            [
                'name' => 'Cozinheiro',
                'description' => 'Cozinheiro do restaurante',
                'is_active' => true,
                'tenant_id' => $tenant->id,
            ],
            [
                'name' => 'Caixa',
                'description' => 'Operador de caixa',
                'is_active' => true,
                'tenant_id' => $tenant->id,
            ],
            [
                'name' => 'Cliente',
                'description' => 'Cliente do restaurante',
                'is_active' => true,
                'tenant_id' => $tenant->id,
            ],
        ];

        foreach ($profiles as $profileData) {
            $profile = Profile::firstOrCreate(
                ['name' => $profileData['name'], 'tenant_id' => $tenant->id],
                $profileData
            );
            $this->command->info("✅ Profile: " . ($profile->wasRecentlyCreated ? 'criado' : 'já existe') . " - {$profile->name}");
        }
    }
}
