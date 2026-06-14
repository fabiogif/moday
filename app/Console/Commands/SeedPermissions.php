<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Permission;
use App\Models\Profile;
use App\Models\User;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;

class SeedPermissions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'permissions:seed {--tenant=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Seed basic permissions and profiles for the system';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $tenantId = $this->option('tenant');
        
        if (!$tenantId) {
            $this->error('Tenant ID é obrigatório. Use --tenant=ID');
            return 1;
        }

        $tenant = Tenant::find($tenantId);
        if (!$tenant) {
            $this->error('Tenant não encontrado.');
            return 1;
        }

        $this->info("Criando permissões para o tenant: {$tenant->name}");

        DB::beginTransaction();

        try {
            // Criar permissões básicas
            $permissions = $this->createBasicPermissions($tenantId);
            $this->info('Permissões criadas: ' . count($permissions));

            // Criar perfis básicos
            $profiles = $this->createBasicProfiles($tenantId, $permissions);
            $this->info('Perfis criados: ' . count($profiles));

            // Atribuir perfil admin ao primeiro usuário do tenant
            $this->assignAdminProfile($tenantId, $profiles['admin']);

            DB::commit();
            $this->info('Permissões e perfis criados com sucesso!');

        } catch (\Exception $e) {
            DB::rollBack();
            $this->error('Erro ao criar permissões: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }

    private function createBasicPermissions($tenantId)
    {
        $permissions = [
            // Usuários
            ['name' => 'Listar Usuários', 'slug' => 'users.index', 'description' => 'Visualizar lista de usuários', 'module' => 'users', 'action' => 'index', 'resource' => 'users'],
            ['name' => 'Criar Usuários', 'slug' => 'users.create', 'description' => 'Criar novos usuários', 'module' => 'users', 'action' => 'create', 'resource' => 'users'],
            ['name' => 'Visualizar Usuário', 'slug' => 'users.show', 'description' => 'Visualizar detalhes do usuário', 'module' => 'users', 'action' => 'show', 'resource' => 'users'],
            ['name' => 'Editar Usuários', 'slug' => 'users.update', 'description' => 'Editar usuários existentes', 'module' => 'users', 'action' => 'update', 'resource' => 'users'],
            ['name' => 'Excluir Usuários', 'slug' => 'users.delete', 'description' => 'Excluir usuários', 'module' => 'users', 'action' => 'delete', 'resource' => 'users'],
            ['name' => 'Gerenciar Usuários', 'slug' => 'users.manage', 'description' => 'Gerenciar todos os aspectos de usuários', 'module' => 'users', 'action' => 'manage', 'resource' => 'users'],

            // Perfis
            ['name' => 'Listar Perfis', 'slug' => 'profiles.index', 'description' => 'Visualizar lista de perfis', 'module' => 'profiles', 'action' => 'index', 'resource' => 'profiles'],
            ['name' => 'Criar Perfis', 'slug' => 'profiles.create', 'description' => 'Criar novos perfis', 'module' => 'profiles', 'action' => 'create', 'resource' => 'profiles'],
            ['name' => 'Visualizar Perfil', 'slug' => 'profiles.show', 'description' => 'Visualizar detalhes do perfil', 'module' => 'profiles', 'action' => 'show', 'resource' => 'profiles'],
            ['name' => 'Editar Perfis', 'slug' => 'profiles.update', 'description' => 'Editar perfis existentes', 'module' => 'profiles', 'action' => 'update', 'resource' => 'profiles'],
            ['name' => 'Excluir Perfis', 'slug' => 'profiles.delete', 'description' => 'Excluir perfis', 'module' => 'profiles', 'action' => 'delete', 'resource' => 'profiles'],
            ['name' => 'Gerenciar Permissões de Perfis', 'slug' => 'profiles.permissions', 'description' => 'Gerenciar permissões dos perfis', 'module' => 'profiles', 'action' => 'permissions', 'resource' => 'profiles'],

            // Permissões
            ['name' => 'Listar Permissões', 'slug' => 'permissions.index', 'description' => 'Visualizar lista de permissões', 'module' => 'permissions', 'action' => 'index', 'resource' => 'permissions'],
            ['name' => 'Criar Permissões', 'slug' => 'permissions.create', 'description' => 'Criar novas permissões', 'module' => 'permissions', 'action' => 'create', 'resource' => 'permissions'],
            ['name' => 'Visualizar Permissão', 'slug' => 'permissions.show', 'description' => 'Visualizar detalhes da permissão', 'module' => 'permissions', 'action' => 'show', 'resource' => 'permissions'],
            ['name' => 'Editar Permissões', 'slug' => 'permissions.update', 'description' => 'Editar permissões existentes', 'module' => 'permissions', 'action' => 'update', 'resource' => 'permissions'],
            ['name' => 'Excluir Permissões', 'slug' => 'permissions.delete', 'description' => 'Excluir permissões', 'module' => 'permissions', 'action' => 'delete', 'resource' => 'permissions'],

            // Admin
            ['name' => 'Acesso ao Admin', 'slug' => 'admin.access', 'description' => 'Acessar painel administrativo', 'module' => 'admin', 'action' => 'access', 'resource' => 'admin'],
            ['name' => 'Dashboard Admin', 'slug' => 'admin.dashboard', 'description' => 'Acessar dashboard administrativo', 'module' => 'admin', 'action' => 'dashboard', 'resource' => 'admin'],
            ['name' => 'Configurações Admin', 'slug' => 'admin.settings', 'description' => 'Acessar configurações administrativas', 'module' => 'admin', 'action' => 'settings', 'resource' => 'admin'],

            // Produtos
            ['name' => 'Listar Produtos', 'slug' => 'products.index', 'description' => 'Visualizar lista de produtos', 'module' => 'products', 'action' => 'index', 'resource' => 'products'],
            ['name' => 'Criar Produtos', 'slug' => 'products.create', 'description' => 'Criar novos produtos', 'module' => 'products', 'action' => 'create', 'resource' => 'products'],
            ['name' => 'Visualizar Produto', 'slug' => 'products.show', 'description' => 'Visualizar detalhes do produto', 'module' => 'products', 'action' => 'show', 'resource' => 'products'],
            ['name' => 'Editar Produtos', 'slug' => 'products.update', 'description' => 'Editar produtos existentes', 'module' => 'products', 'action' => 'update', 'resource' => 'products'],
            ['name' => 'Excluir Produtos', 'slug' => 'products.delete', 'description' => 'Excluir produtos', 'module' => 'products', 'action' => 'delete', 'resource' => 'products'],

            // Pedidos
            ['name' => 'Listar Pedidos', 'slug' => 'orders.index', 'description' => 'Visualizar lista de pedidos', 'module' => 'orders', 'action' => 'index', 'resource' => 'orders'],
            ['name' => 'Criar Pedidos', 'slug' => 'orders.create', 'description' => 'Criar novos pedidos', 'module' => 'orders', 'action' => 'create', 'resource' => 'orders'],
            ['name' => 'Visualizar Pedido', 'slug' => 'orders.show', 'description' => 'Visualizar detalhes do pedido', 'module' => 'orders', 'action' => 'show', 'resource' => 'orders'],
            ['name' => 'Editar Pedidos', 'slug' => 'orders.update', 'description' => 'Editar pedidos existentes', 'module' => 'orders', 'action' => 'update', 'resource' => 'orders'],
            ['name' => 'Excluir Pedidos', 'slug' => 'orders.delete', 'description' => 'Excluir pedidos', 'module' => 'orders', 'action' => 'delete', 'resource' => 'orders'],

            // Categorias
            ['name' => 'Listar Categorias', 'slug' => 'categories.index', 'description' => 'Visualizar lista de categorias', 'module' => 'categories', 'action' => 'index', 'resource' => 'categories'],
            ['name' => 'Criar Categorias', 'slug' => 'categories.create', 'description' => 'Criar novas categorias', 'module' => 'categories', 'action' => 'create', 'resource' => 'categories'],
            ['name' => 'Visualizar Categoria', 'slug' => 'categories.show', 'description' => 'Visualizar detalhes da categoria', 'module' => 'categories', 'action' => 'show', 'resource' => 'categories'],
            ['name' => 'Editar Categorias', 'slug' => 'categories.update', 'description' => 'Editar categorias existentes', 'module' => 'categories', 'action' => 'update', 'resource' => 'categories'],
            ['name' => 'Excluir Categorias', 'slug' => 'categories.delete', 'description' => 'Excluir categorias', 'module' => 'categories', 'action' => 'delete', 'resource' => 'categories'],

            // Mesas
            ['name' => 'Listar Mesas', 'slug' => 'tables.index', 'description' => 'Visualizar lista de mesas', 'module' => 'tables', 'action' => 'index', 'resource' => 'tables'],
            ['name' => 'Criar Mesas', 'slug' => 'tables.create', 'description' => 'Criar novas mesas', 'module' => 'tables', 'action' => 'create', 'resource' => 'tables'],
            ['name' => 'Visualizar Mesa', 'slug' => 'tables.show', 'description' => 'Visualizar detalhes da mesa', 'module' => 'tables', 'action' => 'show', 'resource' => 'tables'],
            ['name' => 'Editar Mesas', 'slug' => 'tables.update', 'description' => 'Editar mesas existentes', 'module' => 'tables', 'action' => 'update', 'resource' => 'tables'],
            ['name' => 'Excluir Mesas', 'slug' => 'tables.delete', 'description' => 'Excluir mesas', 'module' => 'tables', 'action' => 'delete', 'resource' => 'tables'],

            // Clientes
            ['name' => 'Listar Clientes', 'slug' => 'clients.index', 'description' => 'Visualizar lista de clientes', 'module' => 'clients', 'action' => 'index', 'resource' => 'clients'],
            ['name' => 'Criar Clientes', 'slug' => 'clients.create', 'description' => 'Criar novos clientes', 'module' => 'clients', 'action' => 'create', 'resource' => 'clients'],
            ['name' => 'Visualizar Cliente', 'slug' => 'clients.show', 'description' => 'Visualizar detalhes do cliente', 'module' => 'clients', 'action' => 'show', 'resource' => 'clients'],
            ['name' => 'Editar Clientes', 'slug' => 'clients.update', 'description' => 'Editar clientes existentes', 'module' => 'clients', 'action' => 'update', 'resource' => 'clients'],
            ['name' => 'Excluir Clientes', 'slug' => 'clients.delete', 'description' => 'Excluir clientes', 'module' => 'clients', 'action' => 'delete', 'resource' => 'clients'],

            // Relatórios
            ['name' => 'Listar Relatórios', 'slug' => 'reports.index', 'description' => 'Visualizar lista de relatórios', 'module' => 'reports', 'action' => 'index', 'resource' => 'reports'],
            ['name' => 'Criar Relatórios', 'slug' => 'reports.create', 'description' => 'Criar novos relatórios', 'module' => 'reports', 'action' => 'create', 'resource' => 'reports'],
            ['name' => 'Visualizar Relatório', 'slug' => 'reports.show', 'description' => 'Visualizar detalhes do relatório', 'module' => 'reports', 'action' => 'show', 'resource' => 'reports'],
            ['name' => 'Exportar Relatórios', 'slug' => 'reports.export', 'description' => 'Exportar relatórios', 'module' => 'reports', 'action' => 'export', 'resource' => 'reports'],
        ];

        $createdPermissions = [];
        foreach ($permissions as $permissionData) {
            $permissionData['tenant_id'] = $tenantId;
            $permissionData['is_active'] = true;
            
            $permission = Permission::create($permissionData);
            $createdPermissions[$permission->slug] = $permission;
        }

        return $createdPermissions;
    }

    private function createBasicProfiles($tenantId, $permissions)
    {
        $profiles = [
            'admin' => [
                'name' => 'Administrador',
                'description' => 'Acesso total ao sistema',
                'permissions' => array_keys($permissions)
            ],
            'manager' => [
                'name' => 'Gerente',
                'description' => 'Acesso de gerenciamento',
                'permissions' => [
                    'users.index', 'users.show', 'users.update',
                    'products.index', 'products.create', 'products.show', 'products.update', 'products.delete',
                    'orders.index', 'orders.create', 'orders.show', 'orders.update', 'orders.delete',
                    'categories.index', 'categories.create', 'categories.show', 'categories.update', 'categories.delete',
                    'tables.index', 'tables.create', 'tables.show', 'tables.update', 'tables.delete',
                    'clients.index', 'clients.create', 'clients.show', 'clients.update', 'clients.delete',
                    'reports.index', 'reports.create', 'reports.show', 'reports.export',
                ]
            ],
            'employee' => [
                'name' => 'Funcionário',
                'description' => 'Acesso básico do funcionário',
                'permissions' => [
                    'products.index', 'products.show',
                    'orders.index', 'orders.create', 'orders.show', 'orders.update',
                    'tables.index', 'tables.show', 'tables.update',
                    'clients.index', 'clients.create', 'clients.show', 'clients.update',
                ]
            ]
        ];

        $createdProfiles = [];
        foreach ($profiles as $key => $profileData) {
            $profile = Profile::create([
                'name' => $profileData['name'],
                'description' => $profileData['description'],
                'tenant_id' => $tenantId,
                'is_active' => true,
            ]);

            // Atribuir permissões ao perfil
            $permissionIds = [];
            foreach ($profileData['permissions'] as $permissionSlug) {
                if (isset($permissions[$permissionSlug])) {
                    $permissionIds[] = $permissions[$permissionSlug]->id;
                }
            }
            
            $profile->permissions()->sync($permissionIds);
            $createdProfiles[$key] = $profile;
        }

        return $createdProfiles;
    }

    private function assignAdminProfile($tenantId, $adminProfile)
    {
        $firstUser = User::where('tenant_id', $tenantId)->first();
        
        if ($firstUser) {
            $firstUser->profiles()->sync([$adminProfile->id]);
            $this->info("Perfil admin atribuído ao usuário: {$firstUser->name}");
        }
    }
}
