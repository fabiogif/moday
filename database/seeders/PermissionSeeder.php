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

            // ============ MÓDULO FINANCEIRO ============

            // Módulo de Fornecedores
            ['name' => 'Visualizar Fornecedores', 'slug' => 'suppliers.index', 'description' => 'Visualizar lista de fornecedores', 'module' => 'suppliers', 'action' => 'index', 'resource' => 'supplier', 'is_active' => true],
            ['name' => 'Ver Detalhes do Fornecedor', 'slug' => 'suppliers.show', 'description' => 'Ver detalhes de um fornecedor', 'module' => 'suppliers', 'action' => 'show', 'resource' => 'supplier', 'is_active' => true],
            ['name' => 'Criar Fornecedores', 'slug' => 'suppliers.store', 'description' => 'Criar novos fornecedores', 'module' => 'suppliers', 'action' => 'store', 'resource' => 'supplier', 'is_active' => true],
            ['name' => 'Editar Fornecedores', 'slug' => 'suppliers.update', 'description' => 'Editar fornecedores existentes', 'module' => 'suppliers', 'action' => 'update', 'resource' => 'supplier', 'is_active' => true],
            ['name' => 'Excluir Fornecedores', 'slug' => 'suppliers.destroy', 'description' => 'Excluir fornecedores', 'module' => 'suppliers', 'action' => 'destroy', 'resource' => 'supplier', 'is_active' => true],
            ['name' => 'Verificar Documento de Fornecedor', 'slug' => 'suppliers.check-document', 'description' => 'Verificar se documento de fornecedor já existe', 'module' => 'suppliers', 'action' => 'check-document', 'resource' => 'supplier', 'is_active' => true],

            // Módulo de Categorias Financeiras
            ['name' => 'Visualizar Categorias Financeiras', 'slug' => 'financial-categories.index', 'description' => 'Visualizar lista de categorias financeiras', 'module' => 'financial-categories', 'action' => 'index', 'resource' => 'financial-category', 'is_active' => true],
            ['name' => 'Ver Detalhes da Categoria Financeira', 'slug' => 'financial-categories.show', 'description' => 'Ver detalhes de uma categoria financeira', 'module' => 'financial-categories', 'action' => 'show', 'resource' => 'financial-category', 'is_active' => true],
            ['name' => 'Criar Categorias Financeiras', 'slug' => 'financial-categories.store', 'description' => 'Criar novas categorias financeiras', 'module' => 'financial-categories', 'action' => 'store', 'resource' => 'financial-category', 'is_active' => true],
            ['name' => 'Editar Categorias Financeiras', 'slug' => 'financial-categories.update', 'description' => 'Editar categorias financeiras existentes', 'module' => 'financial-categories', 'action' => 'update', 'resource' => 'financial-category', 'is_active' => true],
            ['name' => 'Excluir Categorias Financeiras', 'slug' => 'financial-categories.destroy', 'description' => 'Excluir categorias financeiras', 'module' => 'financial-categories', 'action' => 'destroy', 'resource' => 'financial-category', 'is_active' => true],

            // Módulo de Despesas
            ['name' => 'Visualizar Despesas', 'slug' => 'expenses.index', 'description' => 'Visualizar lista de despesas', 'module' => 'expenses', 'action' => 'index', 'resource' => 'expense', 'is_active' => true],
            ['name' => 'Ver Detalhes da Despesa', 'slug' => 'expenses.show', 'description' => 'Ver detalhes de uma despesa', 'module' => 'expenses', 'action' => 'show', 'resource' => 'expense', 'is_active' => true],
            ['name' => 'Criar Despesas', 'slug' => 'expenses.store', 'description' => 'Criar novas despesas', 'module' => 'expenses', 'action' => 'store', 'resource' => 'expense', 'is_active' => true],
            ['name' => 'Editar Despesas', 'slug' => 'expenses.update', 'description' => 'Editar despesas existentes', 'module' => 'expenses', 'action' => 'update', 'resource' => 'expense', 'is_active' => true],
            ['name' => 'Excluir Despesas', 'slug' => 'expenses.destroy', 'description' => 'Excluir despesas', 'module' => 'expenses', 'action' => 'destroy', 'resource' => 'expense', 'is_active' => true],
            ['name' => 'Visualizar Estatísticas de Despesas', 'slug' => 'expenses.stats', 'description' => 'Visualizar estatísticas e totais de despesas', 'module' => 'expenses', 'action' => 'stats', 'resource' => 'expense', 'is_active' => true],
            ['name' => 'Anexar Arquivo à Despesa', 'slug' => 'expenses.attachment', 'description' => 'Anexar comprovante ou arquivo a uma despesa', 'module' => 'expenses', 'action' => 'attachment', 'resource' => 'expense', 'is_active' => true],

            // Módulo de Contas a Pagar
            ['name' => 'Visualizar Contas a Pagar', 'slug' => 'accounts-payable.index', 'description' => 'Visualizar lista de contas a pagar', 'module' => 'accounts-payable', 'action' => 'index', 'resource' => 'account-payable', 'is_active' => true],
            ['name' => 'Ver Detalhes da Conta a Pagar', 'slug' => 'accounts-payable.show', 'description' => 'Ver detalhes de uma conta a pagar', 'module' => 'accounts-payable', 'action' => 'show', 'resource' => 'account-payable', 'is_active' => true],
            ['name' => 'Criar Contas a Pagar', 'slug' => 'accounts-payable.store', 'description' => 'Criar novas contas a pagar', 'module' => 'accounts-payable', 'action' => 'store', 'resource' => 'account-payable', 'is_active' => true],
            ['name' => 'Editar Contas a Pagar', 'slug' => 'accounts-payable.update', 'description' => 'Editar contas a pagar existentes', 'module' => 'accounts-payable', 'action' => 'update', 'resource' => 'account-payable', 'is_active' => true],
            ['name' => 'Excluir Contas a Pagar', 'slug' => 'accounts-payable.destroy', 'description' => 'Excluir contas a pagar', 'module' => 'accounts-payable', 'action' => 'destroy', 'resource' => 'account-payable', 'is_active' => true],
            ['name' => 'Pagar Conta', 'slug' => 'accounts-payable.pay', 'description' => 'Marcar conta como paga', 'module' => 'accounts-payable', 'action' => 'pay', 'resource' => 'account-payable', 'is_active' => true],

            // Módulo de Contas a Receber
            ['name' => 'Visualizar Contas a Receber', 'slug' => 'accounts-receivable.index', 'description' => 'Visualizar lista de contas a receber', 'module' => 'accounts-receivable', 'action' => 'index', 'resource' => 'account-receivable', 'is_active' => true],
            ['name' => 'Ver Detalhes da Conta a Receber', 'slug' => 'accounts-receivable.show', 'description' => 'Ver detalhes de uma conta a receber', 'module' => 'accounts-receivable', 'action' => 'show', 'resource' => 'account-receivable', 'is_active' => true],
            ['name' => 'Criar Contas a Receber', 'slug' => 'accounts-receivable.store', 'description' => 'Criar novas contas a receber', 'module' => 'accounts-receivable', 'action' => 'store', 'resource' => 'account-receivable', 'is_active' => true],
            ['name' => 'Editar Contas a Receber', 'slug' => 'accounts-receivable.update', 'description' => 'Editar contas a receber existentes', 'module' => 'accounts-receivable', 'action' => 'update', 'resource' => 'account-receivable', 'is_active' => true],
            ['name' => 'Excluir Contas a Receber', 'slug' => 'accounts-receivable.destroy', 'description' => 'Excluir contas a receber', 'module' => 'accounts-receivable', 'action' => 'destroy', 'resource' => 'account-receivable', 'is_active' => true],
            ['name' => 'Receber Conta', 'slug' => 'accounts-receivable.receive', 'description' => 'Marcar conta como recebida', 'module' => 'accounts-receivable', 'action' => 'receive', 'resource' => 'account-receivable', 'is_active' => true],

            // DistribTec — Pedidos de Venda
            ['name' => 'Visualizar Pedidos de Venda', 'slug' => 'sale-orders.index', 'description' => 'Visualizar lista de pedidos de venda', 'module' => 'sale-orders', 'action' => 'index', 'resource' => 'sale-order', 'is_active' => true],
            ['name' => 'Criar Pedidos de Venda', 'slug' => 'sale-orders.store', 'description' => 'Criar novos pedidos de venda', 'module' => 'sale-orders', 'action' => 'store', 'resource' => 'sale-order', 'is_active' => true],
            ['name' => 'Editar Pedidos de Venda', 'slug' => 'sale-orders.update', 'description' => 'Editar pedidos de venda existentes', 'module' => 'sale-orders', 'action' => 'update', 'resource' => 'sale-order', 'is_active' => true],
            ['name' => 'Excluir Pedidos de Venda', 'slug' => 'sale-orders.destroy', 'description' => 'Excluir pedidos de venda', 'module' => 'sale-orders', 'action' => 'destroy', 'resource' => 'sale-order', 'is_active' => true],
            ['name' => 'Confirmar Picking de Venda', 'slug' => 'picking.confirm', 'description' => 'Confirmar separação de itens do pedido de venda', 'module' => 'sale-orders', 'action' => 'picking.confirm', 'resource' => 'sale-order', 'is_active' => true],
            ['name' => 'Solicitar Emissão Fiscal', 'slug' => 'sale-orders.fiscal.request', 'description' => 'Solicitar emissão de NF-e para pedido de venda', 'module' => 'sale-orders', 'action' => 'fiscal.request', 'resource' => 'sale-order', 'is_active' => true],

            // DistribTec — Ofertas Automáticas
            ['name' => 'Visualizar Ofertas', 'slug' => 'offer-rules.index', 'description' => 'Visualizar lista de ofertas automáticas', 'module' => 'offer-rules', 'action' => 'index', 'resource' => 'offer-rule', 'is_active' => true],
            ['name' => 'Criar Ofertas', 'slug' => 'offer-rules.store', 'description' => 'Criar novas ofertas automáticas', 'module' => 'offer-rules', 'action' => 'store', 'resource' => 'offer-rule', 'is_active' => true],
            ['name' => 'Editar Ofertas', 'slug' => 'offer-rules.update', 'description' => 'Editar ofertas automáticas existentes', 'module' => 'offer-rules', 'action' => 'update', 'resource' => 'offer-rule', 'is_active' => true],
            ['name' => 'Excluir Ofertas', 'slug' => 'offer-rules.destroy', 'description' => 'Excluir ofertas automáticas', 'module' => 'offer-rules', 'action' => 'destroy', 'resource' => 'offer-rule', 'is_active' => true],

            // DistribTec — Pedidos de Compra
            ['name' => 'Visualizar Pedidos de Compra', 'slug' => 'purchase-orders.index', 'description' => 'Visualizar lista de pedidos de compra', 'module' => 'purchase-orders', 'action' => 'index', 'resource' => 'purchase-order', 'is_active' => true],
            ['name' => 'Criar Pedidos de Compra', 'slug' => 'purchase-orders.store', 'description' => 'Criar novos pedidos de compra', 'module' => 'purchase-orders', 'action' => 'store', 'resource' => 'purchase-order', 'is_active' => true],
            ['name' => 'Editar Pedidos de Compra', 'slug' => 'purchase-orders.update', 'description' => 'Editar pedidos de compra existentes', 'module' => 'purchase-orders', 'action' => 'update', 'resource' => 'purchase-order', 'is_active' => true],
            ['name' => 'Excluir Pedidos de Compra', 'slug' => 'purchase-orders.destroy', 'description' => 'Excluir pedidos de compra', 'module' => 'purchase-orders', 'action' => 'destroy', 'resource' => 'purchase-order', 'is_active' => true],

            // DistribTec — Armazéns
            ['name' => 'Visualizar Armazéns', 'slug' => 'warehouses.index', 'description' => 'Visualizar lista de armazéns', 'module' => 'warehouses', 'action' => 'index', 'resource' => 'warehouse', 'is_active' => true],
            ['name' => 'Criar Armazéns', 'slug' => 'warehouses.store', 'description' => 'Criar novos armazéns', 'module' => 'warehouses', 'action' => 'store', 'resource' => 'warehouse', 'is_active' => true],
            ['name' => 'Editar Armazéns', 'slug' => 'warehouses.update', 'description' => 'Editar armazéns existentes', 'module' => 'warehouses', 'action' => 'update', 'resource' => 'warehouse', 'is_active' => true],
            ['name' => 'Excluir Armazéns', 'slug' => 'warehouses.destroy', 'description' => 'Excluir armazéns', 'module' => 'warehouses', 'action' => 'destroy', 'resource' => 'warehouse', 'is_active' => true],

            // DistribTec — Lotes
            ['name' => 'Visualizar Lotes', 'slug' => 'batches.index', 'description' => 'Visualizar lista de lotes', 'module' => 'batches', 'action' => 'index', 'resource' => 'batch', 'is_active' => true],
            ['name' => 'Quarentena de Lote', 'slug' => 'batches.quarantine', 'description' => 'Colocar lote em quarentena', 'module' => 'batches', 'action' => 'quarantine', 'resource' => 'batch', 'is_active' => true],
            ['name' => 'Recall de Lote', 'slug' => 'batches.recall', 'description' => 'Realizar recall de lote', 'module' => 'batches', 'action' => 'recall', 'resource' => 'batch', 'is_active' => true],

            // DistribTec — Movimentações de Estoque
            ['name' => 'Visualizar Movimentações de Estoque', 'slug' => 'stock-movements.index', 'description' => 'Visualizar lista de movimentações de estoque', 'module' => 'stock-movements', 'action' => 'index', 'resource' => 'stock-movement', 'is_active' => true],
            ['name' => 'Criar Movimentações de Estoque', 'slug' => 'stock-movements.store', 'description' => 'Registrar movimentações de estoque', 'module' => 'stock-movements', 'action' => 'store', 'resource' => 'stock-movement', 'is_active' => true],

            // DistribTec — Tabelas de Preço
            ['name' => 'Visualizar Tabelas de Preço', 'slug' => 'price-tables.index', 'description' => 'Visualizar lista de tabelas de preço', 'module' => 'price-tables', 'action' => 'index', 'resource' => 'price-table', 'is_active' => true],
            ['name' => 'Criar Tabelas de Preço', 'slug' => 'price-tables.store', 'description' => 'Criar novas tabelas de preço', 'module' => 'price-tables', 'action' => 'store', 'resource' => 'price-table', 'is_active' => true],
            ['name' => 'Editar Tabelas de Preço', 'slug' => 'price-tables.update', 'description' => 'Editar tabelas de preço existentes', 'module' => 'price-tables', 'action' => 'update', 'resource' => 'price-table', 'is_active' => true],
            ['name' => 'Excluir Tabelas de Preço', 'slug' => 'price-tables.destroy', 'description' => 'Excluir tabelas de preço', 'module' => 'price-tables', 'action' => 'destroy', 'resource' => 'price-table', 'is_active' => true],

            // DistribTec — Romaneios / Expedição
            ['name' => 'Visualizar Romaneios', 'slug' => 'shipments.index', 'description' => 'Visualizar lista de romaneios', 'module' => 'shipments', 'action' => 'index', 'resource' => 'shipment', 'is_active' => true],
            ['name' => 'Criar Romaneios', 'slug' => 'shipments.store', 'description' => 'Criar novos romaneios', 'module' => 'shipments', 'action' => 'store', 'resource' => 'shipment', 'is_active' => true],
            ['name' => 'Despachar Romaneio', 'slug' => 'shipments.dispatch', 'description' => 'Despachar romaneio para entrega', 'module' => 'shipments', 'action' => 'dispatch', 'resource' => 'shipment', 'is_active' => true],
            ['name' => 'Confirmar Entrega de Romaneio', 'slug' => 'shipments.deliver', 'description' => 'Confirmar entrega de romaneio', 'module' => 'shipments', 'action' => 'deliver', 'resource' => 'shipment', 'is_active' => true],

            // DistribTec — Inventário Cíclico
            ['name' => 'Visualizar Inventários', 'slug' => 'inventory-counts.index', 'description' => 'Visualizar lista de inventários cíclicos', 'module' => 'inventory-counts', 'action' => 'index', 'resource' => 'inventory-count', 'is_active' => true],
            ['name' => 'Criar Inventários', 'slug' => 'inventory-counts.store', 'description' => 'Criar novos inventários cíclicos', 'module' => 'inventory-counts', 'action' => 'store', 'resource' => 'inventory-count', 'is_active' => true],
            ['name' => 'Editar Inventários', 'slug' => 'inventory-counts.update', 'description' => 'Adicionar itens e fechar inventários', 'module' => 'inventory-counts', 'action' => 'update', 'resource' => 'inventory-count', 'is_active' => true],

            // DistribTec — Auditoria
            ['name' => 'Visualizar Logs de Auditoria', 'slug' => 'audit-logs.index', 'description' => 'Visualizar logs de auditoria do sistema', 'module' => 'audit-logs', 'action' => 'index', 'resource' => 'audit-log', 'is_active' => true],
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
