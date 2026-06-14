<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Buscar tenant padrão
        $tenant = \App\Models\Tenant::first();
        if (!$tenant) {
            $this->command->error('❌ Nenhum tenant encontrado. Execute TenantsTableSeeder primeiro.');
            return;
        }

        $roles = [
            [
                'name' => 'Super Admin',
                'slug' => 'super-admin',
                'description' => 'Acesso total ao sistema',
                'level' => 1,
                'is_active' => true,
                'tenant_id' => $tenant->id,
            ],
            [
                'name' => 'Administrador',
                'slug' => 'admin',
                'description' => 'Administrador do sistema',
                'level' => 2,
                'is_active' => true,
                'tenant_id' => $tenant->id,
            ],
            [
                'name' => 'Gerente',
                'slug' => 'manager',
                'description' => 'Gerente do restaurante',
                'level' => 3,
                'is_active' => true,
                'tenant_id' => $tenant->id,
            ],
            [
                'name' => 'Garçom',
                'slug' => 'waiter',
                'description' => 'Garçom do restaurante',
                'level' => 4,
                'is_active' => true,
                'tenant_id' => $tenant->id,
            ],
            [
                'name' => 'Cozinheiro',
                'slug' => 'cook',
                'description' => 'Cozinheiro do restaurante',
                'level' => 4,
                'is_active' => true,
                'tenant_id' => $tenant->id,
            ],
            [
                'name' => 'Caixa',
                'slug' => 'cashier',
                'description' => 'Operador de caixa',
                'level' => 4,
                'is_active' => true,
                'tenant_id' => $tenant->id,
            ],
            [
                'name' => 'Cliente',
                'slug' => 'client',
                'description' => 'Cliente do restaurante',
                'level' => 5,
                'is_active' => true,
                'tenant_id' => $tenant->id,
            ],
        ];

        foreach ($roles as $roleData) {
            $role = Role::firstOrCreate(
                ['slug' => $roleData['slug'], 'tenant_id' => $tenant->id],
                $roleData
            );
            $this->command->info("✅ Role: " . ($role->wasRecentlyCreated ? 'criada' : 'já existe') . " - {$role->name}");
        }
    }
}
