<?php

namespace App\Support;

/**
 * Catálogo canônico de permissões padrão por tenant.
 * Usado no seed inicial e no provisionamento de novos tenants.
 */
class AclPermissionDefinitions
{
    public static function defaults(): array
    {
        return [
            ['name' => 'Visualizar Clientes', 'slug' => 'clients.index', 'description' => 'Visualizar lista de clientes', 'module' => 'clients', 'action' => 'index', 'resource' => 'client', 'is_active' => true],
            ['name' => 'Ver Detalhes do Cliente', 'slug' => 'clients.show', 'description' => 'Ver detalhes de um cliente', 'module' => 'clients', 'action' => 'show', 'resource' => 'client', 'is_active' => true],
            ['name' => 'Criar Clientes', 'slug' => 'clients.store', 'description' => 'Criar novos clientes', 'module' => 'clients', 'action' => 'store', 'resource' => 'client', 'is_active' => true],
            ['name' => 'Editar Clientes', 'slug' => 'clients.update', 'description' => 'Editar clientes existentes', 'module' => 'clients', 'action' => 'update', 'resource' => 'client', 'is_active' => true],
            ['name' => 'Excluir Clientes', 'slug' => 'clients.destroy', 'description' => 'Excluir clientes', 'module' => 'clients', 'action' => 'destroy', 'resource' => 'client', 'is_active' => true],

            ['name' => 'Visualizar Produtos', 'slug' => 'products.index', 'description' => 'Visualizar lista de produtos', 'module' => 'products', 'action' => 'index', 'resource' => 'product', 'is_active' => true],
            ['name' => 'Ver Detalhes do Produto', 'slug' => 'products.show', 'description' => 'Ver detalhes de um produto', 'module' => 'products', 'action' => 'show', 'resource' => 'product', 'is_active' => true],
            ['name' => 'Criar Produtos', 'slug' => 'products.store', 'description' => 'Criar novos produtos', 'module' => 'products', 'action' => 'store', 'resource' => 'product', 'is_active' => true],
            ['name' => 'Editar Produtos', 'slug' => 'products.update', 'description' => 'Editar produtos existentes', 'module' => 'products', 'action' => 'update', 'resource' => 'product', 'is_active' => true],
            ['name' => 'Excluir Produtos', 'slug' => 'products.destroy', 'description' => 'Excluir produtos', 'module' => 'products', 'action' => 'destroy', 'resource' => 'product', 'is_active' => true],

            ['name' => 'Visualizar Categorias', 'slug' => 'categories.index', 'description' => 'Visualizar lista de categorias', 'module' => 'categories', 'action' => 'index', 'resource' => 'category', 'is_active' => true],
            ['name' => 'Ver Detalhes da Categoria', 'slug' => 'categories.show', 'description' => 'Ver detalhes de uma categoria', 'module' => 'categories', 'action' => 'show', 'resource' => 'category', 'is_active' => true],
            ['name' => 'Criar Categorias', 'slug' => 'categories.store', 'description' => 'Criar novas categorias', 'module' => 'categories', 'action' => 'store', 'resource' => 'category', 'is_active' => true],
            ['name' => 'Editar Categorias', 'slug' => 'categories.update', 'description' => 'Editar categorias existentes', 'module' => 'categories', 'action' => 'update', 'resource' => 'category', 'is_active' => true],
            ['name' => 'Excluir Categorias', 'slug' => 'categories.destroy', 'description' => 'Excluir categorias', 'module' => 'categories', 'action' => 'destroy', 'resource' => 'category', 'is_active' => true],

            ['name' => 'Visualizar Mesas', 'slug' => 'tables.index', 'description' => 'Visualizar lista de mesas', 'module' => 'tables', 'action' => 'index', 'resource' => 'table', 'is_active' => true],
            ['name' => 'Ver Detalhes da Mesa', 'slug' => 'tables.show', 'description' => 'Ver detalhes de uma mesa', 'module' => 'tables', 'action' => 'show', 'resource' => 'table', 'is_active' => true],
            ['name' => 'Criar Mesas', 'slug' => 'tables.store', 'description' => 'Criar novas mesas', 'module' => 'tables', 'action' => 'store', 'resource' => 'table', 'is_active' => true],
            ['name' => 'Editar Mesas', 'slug' => 'tables.update', 'description' => 'Editar mesas existentes', 'module' => 'tables', 'action' => 'update', 'resource' => 'table', 'is_active' => true],
            ['name' => 'Excluir Mesas', 'slug' => 'tables.destroy', 'description' => 'Excluir mesas', 'module' => 'tables', 'action' => 'destroy', 'resource' => 'table', 'is_active' => true],

            ['name' => 'Visualizar Pedidos', 'slug' => 'orders.index', 'description' => 'Visualizar lista de pedidos', 'module' => 'orders', 'action' => 'index', 'resource' => 'order', 'is_active' => true],
            ['name' => 'Ver Detalhes do Pedido', 'slug' => 'orders.show', 'description' => 'Ver detalhes de um pedido', 'module' => 'orders', 'action' => 'show', 'resource' => 'order', 'is_active' => true],
            ['name' => 'Criar Pedidos', 'slug' => 'orders.store', 'description' => 'Criar novos pedidos', 'module' => 'orders', 'action' => 'store', 'resource' => 'order', 'is_active' => true],
            ['name' => 'Editar Pedidos', 'slug' => 'orders.update', 'description' => 'Editar pedidos existentes', 'module' => 'orders', 'action' => 'update', 'resource' => 'order', 'is_active' => true],
            ['name' => 'Excluir Pedidos', 'slug' => 'orders.destroy', 'description' => 'Excluir pedidos', 'module' => 'orders', 'action' => 'destroy', 'resource' => 'order', 'is_active' => true],
            ['name' => 'Atualizar Status do Pedido', 'slug' => 'orders.status', 'description' => 'Atualizar status de pedidos', 'module' => 'orders', 'action' => 'status', 'resource' => 'order', 'is_active' => true],

            ['name' => 'Visualizar Relatórios', 'slug' => 'reports.index', 'description' => 'Visualizar relatórios do sistema', 'module' => 'reports', 'action' => 'index', 'resource' => 'report', 'is_active' => true],
            ['name' => 'Gerar Relatórios', 'slug' => 'reports.generate', 'description' => 'Gerar relatórios personalizados', 'module' => 'reports', 'action' => 'generate', 'resource' => 'report', 'is_active' => true],

            ['name' => 'Visualizar Funcionários', 'slug' => 'users.index', 'description' => 'Visualizar lista de funcionários', 'module' => 'users', 'action' => 'index', 'resource' => 'user', 'is_active' => true],
            ['name' => 'Ver Detalhes do Funcionário', 'slug' => 'users.show', 'description' => 'Ver detalhes de um funcionário', 'module' => 'users', 'action' => 'show', 'resource' => 'user', 'is_active' => true],
            ['name' => 'Criar Funcionários', 'slug' => 'users.store', 'description' => 'Criar novos funcionários', 'module' => 'users', 'action' => 'store', 'resource' => 'user', 'is_active' => true],
            ['name' => 'Criar Funcionários (API)', 'slug' => 'users.create', 'description' => 'Criar novos funcionários via API', 'module' => 'users', 'action' => 'create', 'resource' => 'user', 'is_active' => true],
            ['name' => 'Editar Funcionários', 'slug' => 'users.update', 'description' => 'Editar funcionários existentes', 'module' => 'users', 'action' => 'update', 'resource' => 'user', 'is_active' => true],
            ['name' => 'Excluir Funcionários', 'slug' => 'users.destroy', 'description' => 'Excluir funcionários', 'module' => 'users', 'action' => 'destroy', 'resource' => 'user', 'is_active' => true],
            ['name' => 'Excluir Funcionários (API)', 'slug' => 'users.delete', 'description' => 'Excluir funcionários via API', 'module' => 'users', 'action' => 'delete', 'resource' => 'user', 'is_active' => true],
            ['name' => 'Alterar Senha de Funcionário', 'slug' => 'users.change-password', 'description' => 'Alterar senha de funcionários', 'module' => 'users', 'action' => 'change-password', 'resource' => 'user', 'is_active' => true],
            ['name' => 'Vincular Perfil ao Funcionário', 'slug' => 'users.assign-profile', 'description' => 'Vincular perfil a um funcionário', 'module' => 'users', 'action' => 'assign-profile', 'resource' => 'user', 'is_active' => true],

            ['name' => 'Visualizar Perfis', 'slug' => 'profiles.index', 'description' => 'Visualizar lista de perfis', 'module' => 'profiles', 'action' => 'index', 'resource' => 'profile', 'is_active' => true],
            ['name' => 'Ver Detalhes do Perfil', 'slug' => 'profiles.show', 'description' => 'Ver detalhes de um perfil', 'module' => 'profiles', 'action' => 'show', 'resource' => 'profile', 'is_active' => true],
            ['name' => 'Criar Perfis', 'slug' => 'profiles.store', 'description' => 'Criar novos perfis', 'module' => 'profiles', 'action' => 'store', 'resource' => 'profile', 'is_active' => true],
            ['name' => 'Editar Perfis', 'slug' => 'profiles.update', 'description' => 'Editar perfis existentes', 'module' => 'profiles', 'action' => 'update', 'resource' => 'profile', 'is_active' => true],
            ['name' => 'Excluir Perfis', 'slug' => 'profiles.destroy', 'description' => 'Excluir perfis', 'module' => 'profiles', 'action' => 'destroy', 'resource' => 'profile', 'is_active' => true],
            ['name' => 'Vincular Permissões ao Perfil', 'slug' => 'profiles.assign-permissions', 'description' => 'Vincular permissões a um perfil', 'module' => 'profiles', 'action' => 'assign-permissions', 'resource' => 'profile', 'is_active' => true],

            ['name' => 'Visualizar Permissões', 'slug' => 'permissions.index', 'description' => 'Visualizar lista de permissões', 'module' => 'permissions', 'action' => 'index', 'resource' => 'permission', 'is_active' => true],
            ['name' => 'Ver Detalhes da Permissão', 'slug' => 'permissions.show', 'description' => 'Ver detalhes de uma permissão', 'module' => 'permissions', 'action' => 'show', 'resource' => 'permission', 'is_active' => true],
            ['name' => 'Criar Permissões', 'slug' => 'permissions.store', 'description' => 'Criar novas permissões', 'module' => 'permissions', 'action' => 'store', 'resource' => 'permission', 'is_active' => true],
            ['name' => 'Editar Permissões', 'slug' => 'permissions.update', 'description' => 'Editar permissões existentes', 'module' => 'permissions', 'action' => 'update', 'resource' => 'permission', 'is_active' => true],
            ['name' => 'Excluir Permissões', 'slug' => 'permissions.destroy', 'description' => 'Excluir permissões', 'module' => 'permissions', 'action' => 'destroy', 'resource' => 'permission', 'is_active' => true],

            ['name' => 'Visualizar Métodos de Pagamento', 'slug' => 'payment-methods.index', 'description' => 'Visualizar lista de métodos de pagamento', 'module' => 'payment-methods', 'action' => 'index', 'resource' => 'payment-method', 'is_active' => true],
            ['name' => 'Ver Detalhes do Método de Pagamento', 'slug' => 'payment-methods.show', 'description' => 'Ver detalhes de um método de pagamento', 'module' => 'payment-methods', 'action' => 'show', 'resource' => 'payment-method', 'is_active' => true],
            ['name' => 'Criar Métodos de Pagamento', 'slug' => 'payment-methods.store', 'description' => 'Criar novos métodos de pagamento', 'module' => 'payment-methods', 'action' => 'store', 'resource' => 'payment-method', 'is_active' => true],
            ['name' => 'Editar Métodos de Pagamento', 'slug' => 'payment-methods.update', 'description' => 'Editar métodos de pagamento existentes', 'module' => 'payment-methods', 'action' => 'update', 'resource' => 'payment-method', 'is_active' => true],
            ['name' => 'Excluir Métodos de Pagamento', 'slug' => 'payment-methods.destroy', 'description' => 'Excluir métodos de pagamento', 'module' => 'payment-methods', 'action' => 'destroy', 'resource' => 'payment-method', 'is_active' => true],

            // DistribTec — Pedidos de Venda
            ['name' => 'Visualizar Pedidos de Venda', 'slug' => 'sale-orders.index', 'description' => 'Visualizar lista de pedidos de venda', 'module' => 'sale-orders', 'action' => 'index', 'resource' => 'sale-order', 'is_active' => true],
            ['name' => 'Criar Pedidos de Venda', 'slug' => 'sale-orders.store', 'description' => 'Criar novos pedidos de venda', 'module' => 'sale-orders', 'action' => 'store', 'resource' => 'sale-order', 'is_active' => true],
            ['name' => 'Editar Pedidos de Venda', 'slug' => 'sale-orders.update', 'description' => 'Editar pedidos de venda existentes', 'module' => 'sale-orders', 'action' => 'update', 'resource' => 'sale-order', 'is_active' => true],
            ['name' => 'Excluir Pedidos de Venda', 'slug' => 'sale-orders.destroy', 'description' => 'Excluir pedidos de venda', 'module' => 'sale-orders', 'action' => 'destroy', 'resource' => 'sale-order', 'is_active' => true],
            ['name' => 'Confirmar Picking de Venda', 'slug' => 'picking.confirm', 'description' => 'Confirmar separação de itens do pedido de venda', 'module' => 'sale-orders', 'action' => 'picking.confirm', 'resource' => 'sale-order', 'is_active' => true],
            ['name' => 'Solicitar Emissão Fiscal', 'slug' => 'sale-orders.fiscal.request', 'description' => 'Solicitar emissão de NF-e para pedido de venda', 'module' => 'sale-orders', 'action' => 'fiscal.request', 'resource' => 'sale-order', 'is_active' => true],

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
    }
}
