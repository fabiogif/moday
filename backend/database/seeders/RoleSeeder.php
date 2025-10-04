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
        $roles = [
            [
                'name' => 'Super Admin',
                'slug' => 'super-admin',
                'description' => 'Acesso total ao sistema',
                'level' => 1,
                'is_active' => true,
            ],
            [
                'name' => 'Administrador',
                'slug' => 'admin',
                'description' => 'Administrador do sistema',
                'level' => 2,
                'is_active' => true,
            ],
            [
                'name' => 'Gerente',
                'slug' => 'manager',
                'description' => 'Gerente do restaurante',
                'level' => 3,
                'is_active' => true,
            ],
            [
                'name' => 'Garçom',
                'slug' => 'waiter',
                'description' => 'Garçom do restaurante',
                'level' => 4,
                'is_active' => true,
            ],
            [
                'name' => 'Cozinheiro',
                'slug' => 'cook',
                'description' => 'Cozinheiro do restaurante',
                'level' => 4,
                'is_active' => true,
            ],
            [
                'name' => 'Caixa',
                'slug' => 'cashier',
                'description' => 'Operador de caixa',
                'level' => 4,
                'is_active' => true,
            ],
            [
                'name' => 'Cliente',
                'slug' => 'client',
                'description' => 'Cliente do restaurante',
                'level' => 5,
                'is_active' => true,
            ],
        ];

        foreach ($roles as $roleData) {
            Role::firstOrCreate(
                ['slug' => $roleData['slug']],
                $roleData
            );
        }
    }
}
