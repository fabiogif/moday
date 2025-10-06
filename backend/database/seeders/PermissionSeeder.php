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
            ['name' => 'Visualizar Clientes', 'slug' => 'clients.index', 'description' => 'Visualizar lista de clientes', 'module' => 'clients', 'action' => 'index', 'resource' => 'client', 'is_active' => true],
            ['name' => 'Ver Detalhes do Cliente', 'slug' => 'clients.show', 'description' => 'Ver detalhes de um cliente', 'module' => 'clients', 'action' => 'show', 'resource' => 'client', 'is_active' => true],
            ['name' => 'Criar Clientes', 'slug' => 'clients.store', 'description' => 'Criar novos clientes', 'module' => 'clients', 'action' => 'store', 'resource' => 'client', 'is_active' => true],
            ['name' => 'Editar Clientes', 'slug' => 'clients.update', 'description' => 'Editar clientes existentes', 'module' => 'clients', 'action' => 'update', 'resource' => 'client', 'is_active' => true],
            ['name' => 'Excluir Clientes', 'slug' => 'clients.destroy', 'description' => 'Excluir clientes', 'module' => 'clients', 'action' => 'destroy', 'resource' => 'client', 'is_active' => true],

            // Módulo de Produtos
            ['name' => 'Visualizar Produtos', 'slug' => 'products.index', 'description' => 'Visualizar lista de produtos', 'module' => 'products', 'action' => 'index', 'resource' => 'product', 'is_active' => true],
            ['name' => 'Ver Detalhes do Produto', 'slug' => 'products.show', 'description' => 'Ver detalhes de um produto', 'module' => 'products', 'action' => 'show', 'resource' => 'product', 'is_active' => true],
            ['name' => 'Criar Produtos', 'slug' => 'products.store', 'description' => 'Criar novos produtos', 'module' => 'products', 'action' => 'store', 'resource' => 'product', 'is_active' => true],
            ['name' => 'Editar Produtos', 'slug' => 'products.update', 'description' => 'Editar produtos existentes', 'module' => 'products', 'action' => 'update', 'resource' => 'product', 'is_active' => true],
            ['name' => 'Excluir Produtos', 'slug' => 'products.destroy', 'description' => 'Excluir produtos', 'module' => 'products', 'action' => 'destroy', 'resource' => 'product', 'is_active' => true],

            // Módulo de Categorias
            ['name' => 'Visualizar Categorias', 'slug' => 'categories.index', 'description' => 'Visualizar lista de categorias', 'module' => 'categories', 'action' => 'index', 'resource' => 'category', 'is_active' => true],
            ['name' => 'Ver Detalhes da Categoria', 'slug' => 'categories.show', 'description' => 'Ver detalhes de uma categoria', 'module' => 'categories', 'action' => 'show', 'resource' => 'category', 'is_active' => true],
            ['name' => 'Criar Categorias', 'slug' => 'categories.store', 'description' => 'Criar novas categorias', 'module' => 'categories', 'action' => 'store', 'resource' => 'category', 'is_active' => true],
            ['name' => 'Editar Categorias', 'slug' => 'categories.update', 'description' => 'Editar categorias existentes', 'module' => 'categories', 'action' => 'update', 'resource' => 'category', 'is_active' => true],
            ['name' => 'Excluir Categorias', 'slug' => 'categories.destroy', 'description' => 'Excluir categorias', 'module' => 'categories', 'action' => 'destroy', 'resource' => 'category', 'is_active' => true],

            // Módulo de Mesas
            ['name' => 'Visualizar Mesas', 'slug' => 'tables.index', 'description' => 'Visualizar lista de mesas', 'module' => 'tables', 'action' => 'index', 'resource' => 'table', 'is_active' => true],
            ['name' => 'Ver Detalhes da Mesa', 'slug' => 'tables.show', 'description' => 'Ver detalhes de uma mesa', 'module' => 'tables', 'action' => 'show', 'resource' => 'table', 'is_active' => true],
            ['name' => 'Criar Mesas', 'slug' => 'tables.store', 'description' => 'Criar novas mesas', 'module' => 'tables', 'action' => 'store', 'resource' => 'table', 'is_active' => true],
            ['name' => 'Editar Mesas', 'slug' => 'tables.update', 'description' => 'Editar mesas existentes', 'module' => 'tables', 'action' => 'update', 'resource' => 'table', 'is_active' => true],
            ['name' => 'Excluir Mesas', 'slug' => 'tables.destroy', 'description' => 'Excluir mesas', 'module' => 'tables', 'action' => 'destroy', 'resource' => 'table', 'is_active' => true],

            // Módulo de Pedidos
            ['name' => 'Visualizar Pedidos', 'slug' => 'orders.index', 'description' => 'Visualizar lista de pedidos', 'module' => 'orders', 'action' => 'index', 'resource' => 'order', 'is_active' => true],
            ['name' => 'Ver Detalhes do Pedido', 'slug' => 'orders.show', 'description' => 'Ver detalhes de um pedido', 'module' => 'orders', 'action' => 'show', 'resource' => 'order', 'is_active' => true],
            ['name' => 'Criar Pedidos', 'slug' => 'orders.store', 'description' => 'Criar novos pedidos', 'module' => 'orders', 'action' => 'store', 'resource' => 'order', 'is_active' => true],
            ['name' => 'Editar Pedidos', 'slug' => 'orders.update', 'description' => 'Editar pedidos existentes', 'module' => 'orders', 'action' => 'update', 'resource' => 'order', 'is_active' => true],
            ['name' => 'Excluir Pedidos', 'slug' => 'orders.destroy', 'description' => 'Excluir pedidos', 'module' => 'orders', 'action' => 'destroy', 'resource' => 'order', 'is_active' => true],
            ['name' => 'Atualizar Status do Pedido', 'slug' => 'orders.status', 'description' => 'Atualizar status de pedidos', 'module' => 'orders', 'action' => 'status', 'resource' => 'order', 'is_active' => true],

            // Módulo de Relatórios
            ['name' => 'Visualizar Relatórios', 'slug' => 'reports.index', 'description' => 'Visualizar relatórios do sistema', 'module' => 'reports', 'action' => 'index', 'resource' => 'report', 'is_active' => true],
            ['name' => 'Gerar Relatórios', 'slug' => 'reports.generate', 'description' => 'Gerar relatórios personalizados', 'module' => 'reports', 'action' => 'generate', 'resource' => 'report', 'is_active' => true],

            // Módulo de Usuários
            ['name' => 'Visualizar Usuários', 'slug' => 'users.index', 'description' => 'Visualizar lista de usuários', 'module' => 'users', 'action' => 'index', 'resource' => 'user', 'is_active' => true],
            ['name' => 'Ver Detalhes do Usuário', 'slug' => 'users.show', 'description' => 'Ver detalhes de um usuário', 'module' => 'users', 'action' => 'show', 'resource' => 'user', 'is_active' => true],
            ['name' => 'Criar Usuários', 'slug' => 'users.store', 'description' => 'Criar novos usuários', 'module' => 'users', 'action' => 'store', 'resource' => 'user', 'is_active' => true],
            ['name' => 'Editar Usuários', 'slug' => 'users.update', 'description' => 'Editar usuários existentes', 'module' => 'users', 'action' => 'update', 'resource' => 'user', 'is_active' => true],
            ['name' => 'Excluir Usuários', 'slug' => 'users.destroy', 'description' => 'Excluir usuários', 'module' => 'users', 'action' => 'destroy', 'resource' => 'user', 'is_active' => true],
            ['name' => 'Alterar Senha de Usuário', 'slug' => 'users.change-password', 'description' => 'Alterar senha de usuários', 'module' => 'users', 'action' => 'change-password', 'resource' => 'user', 'is_active' => true],
            ['name' => 'Vincular Perfil ao Usuário', 'slug' => 'users.assign-profile', 'description' => 'Vincular perfil a um usuário', 'module' => 'users', 'action' => 'assign-profile', 'resource' => 'user', 'is_active' => true],

            // Módulo de Perfis
            ['name' => 'Visualizar Perfis', 'slug' => 'profiles.index', 'description' => 'Visualizar lista de perfis', 'module' => 'profiles', 'action' => 'index', 'resource' => 'profile', 'is_active' => true],
            ['name' => 'Ver Detalhes do Perfil', 'slug' => 'profiles.show', 'description' => 'Ver detalhes de um perfil', 'module' => 'profiles', 'action' => 'show', 'resource' => 'profile', 'is_active' => true],
            ['name' => 'Criar Perfis', 'slug' => 'profiles.store', 'description' => 'Criar novos perfis', 'module' => 'profiles', 'action' => 'store', 'resource' => 'profile', 'is_active' => true],
            ['name' => 'Editar Perfis', 'slug' => 'profiles.update', 'description' => 'Editar perfis existentes', 'module' => 'profiles', 'action' => 'update', 'resource' => 'profile', 'is_active' => true],
            ['name' => 'Excluir Perfis', 'slug' => 'profiles.destroy', 'description' => 'Excluir perfis', 'module' => 'profiles', 'action' => 'destroy', 'resource' => 'profile', 'is_active' => true],
            ['name' => 'Vincular Permissões ao Perfil', 'slug' => 'profiles.assign-permissions', 'description' => 'Vincular permissões a um perfil', 'module' => 'profiles', 'action' => 'assign-permissions', 'resource' => 'profile', 'is_active' => true],

            // Módulo de Permissões
            ['name' => 'Visualizar Permissões', 'slug' => 'permissions.index', 'description' => 'Visualizar lista de permissões', 'module' => 'permissions', 'action' => 'index', 'resource' => 'permission', 'is_active' => true],
            ['name' => 'Ver Detalhes da Permissão', 'slug' => 'permissions.show', 'description' => 'Ver detalhes de uma permissão', 'module' => 'permissions', 'action' => 'show', 'resource' => 'permission', 'is_active' => true],
            ['name' => 'Criar Permissões', 'slug' => 'permissions.store', 'description' => 'Criar novas permissões', 'module' => 'permissions', 'action' => 'store', 'resource' => 'permission', 'is_active' => true],
            ['name' => 'Editar Permissões', 'slug' => 'permissions.update', 'description' => 'Editar permissões existentes', 'module' => 'permissions', 'action' => 'update', 'resource' => 'permission', 'is_active' => true],
            ['name' => 'Excluir Permissões', 'slug' => 'permissions.destroy', 'description' => 'Excluir permissões', 'module' => 'permissions', 'action' => 'destroy', 'resource' => 'permission', 'is_active' => true],

            // Módulo de Métodos de Pagamento
            ['name' => 'Visualizar Métodos de Pagamento', 'slug' => 'payment-methods.index', 'description' => 'Visualizar lista de métodos de pagamento', 'module' => 'payment-methods', 'action' => 'index', 'resource' => 'payment-method', 'is_active' => true],
            ['name' => 'Ver Detalhes do Método de Pagamento', 'slug' => 'payment-methods.show', 'description' => 'Ver detalhes de um método de pagamento', 'module' => 'payment-methods', 'action' => 'show', 'resource' => 'payment-method', 'is_active' => true],
            ['name' => 'Criar Métodos de Pagamento', 'slug' => 'payment-methods.store', 'description' => 'Criar novos métodos de pagamento', 'module' => 'payment-methods', 'action' => 'store', 'resource' => 'payment-method', 'is_active' => true],
            ['name' => 'Editar Métodos de Pagamento', 'slug' => 'payment-methods.update', 'description' => 'Editar métodos de pagamento existentes', 'module' => 'payment-methods', 'action' => 'update', 'resource' => 'payment-method', 'is_active' => true],
            ['name' => 'Excluir Métodos de Pagamento', 'slug' => 'payment-methods.destroy', 'description' => 'Excluir métodos de pagamento', 'module' => 'payment-methods', 'action' => 'destroy', 'resource' => 'payment-method', 'is_active' => true],

            // Módulo de Planos
            ['name' => 'Visualizar Planos', 'slug' => 'plans.index', 'description' => 'Visualizar lista de planos', 'module' => 'plans', 'action' => 'index', 'resource' => 'plan', 'is_active' => true],
            ['name' => 'Ver Detalhes do Plano', 'slug' => 'plans.show', 'description' => 'Ver detalhes de um plano', 'module' => 'plans', 'action' => 'show', 'resource' => 'plan', 'is_active' => true],
            ['name' => 'Criar Planos', 'slug' => 'plans.store', 'description' => 'Criar novos planos', 'module' => 'plans', 'action' => 'store', 'resource' => 'plan', 'is_active' => true],
            ['name' => 'Editar Planos', 'slug' => 'plans.update', 'description' => 'Editar planos existentes', 'module' => 'plans', 'action' => 'update', 'resource' => 'plan', 'is_active' => true],
            ['name' => 'Excluir Planos', 'slug' => 'plans.destroy', 'description' => 'Excluir planos', 'module' => 'plans', 'action' => 'destroy', 'resource' => 'plan', 'is_active' => true],

            // Módulo de Tenants
            ['name' => 'Visualizar Tenants', 'slug' => 'tenants.index', 'description' => 'Visualizar lista de tenants', 'module' => 'tenants', 'action' => 'index', 'resource' => 'tenant', 'is_active' => true],
            ['name' => 'Ver Detalhes do Tenant', 'slug' => 'tenants.show', 'description' => 'Ver detalhes de um tenant', 'module' => 'tenants', 'action' => 'show', 'resource' => 'tenant', 'is_active' => true],
            ['name' => 'Criar Tenants', 'slug' => 'tenants.store', 'description' => 'Criar novos tenants', 'module' => 'tenants', 'action' => 'store', 'resource' => 'tenant', 'is_active' => true],
            ['name' => 'Editar Tenants', 'slug' => 'tenants.update', 'description' => 'Editar tenants existentes', 'module' => 'tenants', 'action' => 'update', 'resource' => 'tenant', 'is_active' => true],
            ['name' => 'Excluir Tenants', 'slug' => 'tenants.destroy', 'description' => 'Excluir tenants', 'module' => 'tenants', 'action' => 'destroy', 'resource' => 'tenant', 'is_active' => true],
        ];

        // Buscar tenant padrão
        $tenant = \App\Models\Tenant::first();
        if (!$tenant) {
            $this->command->error('❌ Nenhum tenant encontrado. Execute TenantsTableSeeder primeiro.');
            return;
        }

        foreach ($permissions as $permissionData) {
            $permission = Permission::firstOrCreate(
                ['slug' => $permissionData['slug'], 'tenant_id' => $tenant->id],
                array_merge($permissionData, ['tenant_id' => $tenant->id])
            );
            $this->command->info("✅ Permissão: " . ($permission->wasRecentlyCreated ? 'criada' : 'já existe') . " - {$permission->name}");
        }
    }
}
