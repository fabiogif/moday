<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Permission;
use App\Models\Profile;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Buscar roles existentes ou criar se não existirem
        $adminRole = Role::firstOrCreate(
            ['slug' => 'admin'],
            ['name' => 'Administrador', 'description' => 'Administrador do sistema', 'level' => 2, 'is_active' => true]
        );

        $managerRole = Role::firstOrCreate(
            ['slug' => 'manager'],
            ['name' => 'Gerente', 'description' => 'Gerente do restaurante', 'level' => 3, 'is_active' => true]
        );

        $userRole = Role::firstOrCreate(
            ['slug' => 'client'],
            ['name' => 'Cliente', 'description' => 'Cliente do restaurante', 'level' => 5, 'is_active' => true]
        );

        // Buscar permissões existentes ou criar se não existirem
        $permissions = [
            ['name' => 'Criar Usuários (Legacy)', 'slug' => 'create_users', 'description' => 'Permissão para criar usuários'],
            ['name' => 'Visualizar Usuários (Legacy)', 'slug' => 'read_users', 'description' => 'Permissão para visualizar usuários'],
            ['name' => 'Editar Usuários (Legacy)', 'slug' => 'update_users', 'description' => 'Permissão para editar usuários'],
            ['name' => 'Excluir Usuários (Legacy)', 'slug' => 'delete_users', 'description' => 'Permissão para excluir usuários'],
            
            ['name' => 'Criar Produtos (Legacy)', 'slug' => 'create_products', 'description' => 'Permissão para criar produtos'],
            ['name' => 'Visualizar Produtos (Legacy)', 'slug' => 'read_products', 'description' => 'Permissão para visualizar produtos'],
            ['name' => 'Editar Produtos (Legacy)', 'slug' => 'update_products', 'description' => 'Permissão para editar produtos'],
            ['name' => 'Excluir Produtos (Legacy)', 'slug' => 'delete_products', 'description' => 'Permissão para excluir produtos'],
            
            ['name' => 'Criar Categorias (Legacy)', 'slug' => 'create_categories', 'description' => 'Permissão para criar categorias'],
            ['name' => 'Visualizar Categorias (Legacy)', 'slug' => 'read_categories', 'description' => 'Permissão para visualizar categorias'],
            ['name' => 'Editar Categorias (Legacy)', 'slug' => 'update_categories', 'description' => 'Permissão para editar categorias'],
            ['name' => 'Excluir Categorias (Legacy)', 'slug' => 'delete_categories', 'description' => 'Permissão para excluir categorias'],
            
            ['name' => 'Criar Pedidos (Legacy)', 'slug' => 'create_orders', 'description' => 'Permissão para criar pedidos'],
            ['name' => 'Visualizar Pedidos (Legacy)', 'slug' => 'read_orders', 'description' => 'Permissão para visualizar pedidos'],
            ['name' => 'Editar Pedidos (Legacy)', 'slug' => 'update_orders', 'description' => 'Permissão para editar pedidos'],
            ['name' => 'Excluir Pedidos (Legacy)', 'slug' => 'delete_orders', 'description' => 'Permissão para excluir pedidos'],
            
            ['name' => 'Criar Mesas (Legacy)', 'slug' => 'create_tables', 'description' => 'Permissão para criar mesas'],
            ['name' => 'Visualizar Mesas (Legacy)', 'slug' => 'read_tables', 'description' => 'Permissão para visualizar mesas'],
            ['name' => 'Editar Mesas (Legacy)', 'slug' => 'update_tables', 'description' => 'Permissão para editar mesas'],
            ['name' => 'Excluir Mesas (Legacy)', 'slug' => 'delete_tables', 'description' => 'Permissão para excluir mesas'],
        ];

        foreach ($permissions as $permissionData) {
            Permission::firstOrCreate(
                ['slug' => $permissionData['slug']],
                array_merge($permissionData, ['tenant_id' => 1])
            );
        }

        // Atribuir todas as permissões ao role de administrador
        $adminRole->permissions()->attach(Permission::all());

        // Atribuir permissões limitadas ao role de gerente
        $managerRole->permissions()->attach(Permission::whereIn('slug', [
            'read_users', 'read_products', 'read_categories', 'read_orders', 'read_tables',
            'create_products', 'update_products', 'create_categories', 'update_categories',
            'create_orders', 'update_orders', 'create_tables', 'update_tables'
        ])->get());

        // Atribuir permissões básicas ao role de usuário
        $userRole->permissions()->attach(Permission::whereIn('slug', [
            'read_products', 'read_categories', 'read_orders', 'read_tables'
        ])->get());

        // Buscar perfis existentes ou criar se não existirem
        $adminProfile = Profile::firstOrCreate(
            ['name' => 'Administrador'],
            ['description' => 'Perfil de administrador com acesso total ao sistema', 'is_active' => true, 'tenant_id' => 1]
        );

        $managerProfile = Profile::firstOrCreate(
            ['name' => 'Gerente'],
            ['description' => 'Perfil de gerente com acesso limitado ao sistema', 'is_active' => true, 'tenant_id' => 1]
        );

        $userProfile = Profile::firstOrCreate(
            ['name' => 'Usuário'],
            ['description' => 'Perfil de usuário com acesso básico ao sistema', 'is_active' => true, 'tenant_id' => 1]
        );

        // Atribuir permissões aos perfis
        $adminProfile->permissions()->attach(Permission::all());
        $managerProfile->permissions()->attach(Permission::whereIn('slug', [
            'read_users', 'read_products', 'read_categories', 'read_orders', 'read_tables',
            'create_products', 'update_products', 'create_categories', 'update_categories',
            'create_orders', 'update_orders', 'create_tables', 'update_tables'
        ])->get());
        $userProfile->permissions()->attach(Permission::whereIn('slug', [
            'read_products', 'read_categories', 'read_orders', 'read_tables'
        ])->get());
    }
}
