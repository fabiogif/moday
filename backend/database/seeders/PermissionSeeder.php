<?php

namespace Database\Seeders;

use App\Models\Permission;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $permissions = [
            // Módulo de Clientes
            [
                'name' => 'Visualizar Clientes',
                'slug' => 'clients.view',
                'description' => 'Visualizar lista de clientes',
                'module' => 'clients',
                'action' => 'view',
                'resource' => 'client',
                'is_active' => true,
            ],
            [
                'name' => 'Criar Clientes',
                'slug' => 'clients.create',
                'description' => 'Criar novos clientes',
                'module' => 'clients',
                'action' => 'create',
                'resource' => 'client',
                'is_active' => true,
            ],
            [
                'name' => 'Editar Clientes',
                'slug' => 'clients.edit',
                'description' => 'Editar clientes existentes',
                'module' => 'clients',
                'action' => 'edit',
                'resource' => 'client',
                'is_active' => true,
            ],
            [
                'name' => 'Excluir Clientes',
                'slug' => 'clients.delete',
                'description' => 'Excluir clientes',
                'module' => 'clients',
                'action' => 'delete',
                'resource' => 'client',
                'is_active' => true,
            ],

            // Módulo de Produtos
            [
                'name' => 'Visualizar Produtos',
                'slug' => 'products.view',
                'description' => 'Visualizar lista de produtos',
                'module' => 'products',
                'action' => 'view',
                'resource' => 'product',
                'is_active' => true,
            ],
            [
                'name' => 'Criar Produtos',
                'slug' => 'products.create',
                'description' => 'Criar novos produtos',
                'module' => 'products',
                'action' => 'create',
                'resource' => 'product',
                'is_active' => true,
            ],
            [
                'name' => 'Editar Produtos',
                'slug' => 'products.edit',
                'description' => 'Editar produtos existentes',
                'module' => 'products',
                'action' => 'edit',
                'resource' => 'product',
                'is_active' => true,
            ],
            [
                'name' => 'Excluir Produtos',
                'slug' => 'products.delete',
                'description' => 'Excluir produtos',
                'module' => 'products',
                'action' => 'delete',
                'resource' => 'product',
                'is_active' => true,
            ],

            // Módulo de Categorias
            [
                'name' => 'Visualizar Categorias',
                'slug' => 'categories.view',
                'description' => 'Visualizar lista de categorias',
                'module' => 'categories',
                'action' => 'view',
                'resource' => 'category',
                'is_active' => true,
            ],
            [
                'name' => 'Criar Categorias',
                'slug' => 'categories.create',
                'description' => 'Criar novas categorias',
                'module' => 'categories',
                'action' => 'create',
                'resource' => 'category',
                'is_active' => true,
            ],
            [
                'name' => 'Editar Categorias',
                'slug' => 'categories.edit',
                'description' => 'Editar categorias existentes',
                'module' => 'categories',
                'action' => 'edit',
                'resource' => 'category',
                'is_active' => true,
            ],
            [
                'name' => 'Excluir Categorias',
                'slug' => 'categories.delete',
                'description' => 'Excluir categorias',
                'module' => 'categories',
                'action' => 'delete',
                'resource' => 'category',
                'is_active' => true,
            ],

            // Módulo de Mesas
            [
                'name' => 'Visualizar Mesas',
                'slug' => 'tables.view',
                'description' => 'Visualizar lista de mesas',
                'module' => 'tables',
                'action' => 'view',
                'resource' => 'table',
                'is_active' => true,
            ],
            [
                'name' => 'Criar Mesas',
                'slug' => 'tables.create',
                'description' => 'Criar novas mesas',
                'module' => 'tables',
                'action' => 'create',
                'resource' => 'table',
                'is_active' => true,
            ],
            [
                'name' => 'Editar Mesas',
                'slug' => 'tables.edit',
                'description' => 'Editar mesas existentes',
                'module' => 'tables',
                'action' => 'edit',
                'resource' => 'table',
                'is_active' => true,
            ],
            [
                'name' => 'Excluir Mesas',
                'slug' => 'tables.delete',
                'description' => 'Excluir mesas',
                'module' => 'tables',
                'action' => 'delete',
                'resource' => 'table',
                'is_active' => true,
            ],

            // Módulo de Pedidos
            [
                'name' => 'Visualizar Pedidos',
                'slug' => 'orders.view',
                'description' => 'Visualizar lista de pedidos',
                'module' => 'orders',
                'action' => 'view',
                'resource' => 'order',
                'is_active' => true,
            ],
            [
                'name' => 'Criar Pedidos',
                'slug' => 'orders.create',
                'description' => 'Criar novos pedidos',
                'module' => 'orders',
                'action' => 'create',
                'resource' => 'order',
                'is_active' => true,
            ],
            [
                'name' => 'Editar Pedidos',
                'slug' => 'orders.edit',
                'description' => 'Editar pedidos existentes',
                'module' => 'orders',
                'action' => 'edit',
                'resource' => 'order',
                'is_active' => true,
            ],
            [
                'name' => 'Excluir Pedidos',
                'slug' => 'orders.delete',
                'description' => 'Excluir pedidos',
                'module' => 'orders',
                'action' => 'delete',
                'resource' => 'order',
                'is_active' => true,
            ],

            // Módulo de Relatórios
            [
                'name' => 'Visualizar Relatórios',
                'slug' => 'reports.view',
                'description' => 'Visualizar relatórios do sistema',
                'module' => 'reports',
                'action' => 'view',
                'resource' => 'report',
                'is_active' => true,
            ],

            // Módulo de Usuários
            [
                'name' => 'Visualizar Usuários',
                'slug' => 'users.view',
                'description' => 'Visualizar lista de usuários',
                'module' => 'users',
                'action' => 'view',
                'resource' => 'user',
                'is_active' => true,
            ],
            [
                'name' => 'Criar Usuários',
                'slug' => 'users.create',
                'description' => 'Criar novos usuários',
                'module' => 'users',
                'action' => 'create',
                'resource' => 'user',
                'is_active' => true,
            ],
            [
                'name' => 'Editar Usuários',
                'slug' => 'users.edit',
                'description' => 'Editar usuários existentes',
                'module' => 'users',
                'action' => 'edit',
                'resource' => 'user',
                'is_active' => true,
            ],
            [
                'name' => 'Excluir Usuários',
                'slug' => 'users.delete',
                'description' => 'Excluir usuários',
                'module' => 'users',
                'action' => 'delete',
                'resource' => 'user',
                'is_active' => true,
            ],
        ];

        foreach ($permissions as $permissionData) {
            Permission::firstOrCreate(
                ['slug' => $permissionData['slug']],
                array_merge($permissionData, ['tenant_id' => 1])
            );
        }
    }
}
