<?php

namespace App\Support;

/**
 * Permissões dos módulos DistribTec (vendas B2B, WMS, logística, etc.).
 */
class DistribtecModulePermissionDefinitions
{
    /**
     * @return array<int, array<string, mixed>>
     */
    public static function definitions(): array
    {
        $permissions = [];

        $modules = [
            'sale-orders' => 'Pedidos de Venda',
            'purchase-orders' => 'Pedidos de Compra',
            'warehouses' => 'Armazéns',
            'batches' => 'Lotes',
            'stock-movements' => 'Movimentações',
            'shipments' => 'Expedição',
            'inventory-counts' => 'Inventário',
            'price-tables' => 'Tabelas de Preço',
            'financial-categories' => 'Categorias Financeiras',
            'accounts-receivable' => 'Contas a Receber',
            'accounts-payable' => 'Contas a Pagar',
            'suppliers' => 'Fornecedores',
        ];

        $actions = [
            'index' => 'Visualizar',
            'store' => 'Criar',
            'update' => 'Editar',
            'destroy' => 'Excluir',
        ];

        foreach ($modules as $module => $label) {
            foreach ($actions as $action => $actionLabel) {
                $permissions[] = [
                    'name' => "{$actionLabel} {$label}",
                    'slug' => "{$module}.{$action}",
                    'description' => "{$actionLabel} - {$label}",
                    'module' => $module,
                    'action' => $action,
                    'resource' => $module,
                    'is_active' => true,
                ];
            }
        }

        foreach ([
            'picking.confirm' => ['module' => 'picking', 'action' => 'confirm', 'resource' => 'picking', 'name' => 'Confirmar Separação'],
            'shipments.dispatch' => ['module' => 'shipments', 'action' => 'dispatch', 'resource' => 'shipments', 'name' => 'Expedir Romaneio'],
            'shipments.deliver' => ['module' => 'shipments', 'action' => 'deliver', 'resource' => 'shipments', 'name' => 'Confirmar Entrega'],
            'batches.recall' => ['module' => 'batches', 'action' => 'recall', 'resource' => 'batches', 'name' => 'Recall de Lote'],
            'batches.quarantine' => ['module' => 'batches', 'action' => 'quarantine', 'resource' => 'batches', 'name' => 'Quarentena de Lote'],
            'audit-logs.index' => ['module' => 'audit-logs', 'action' => 'index', 'resource' => 'audit-logs', 'name' => 'Visualizar Auditoria'],
            'settings.fiscal.update' => ['module' => 'settings', 'action' => 'fiscal.update', 'resource' => 'settings', 'name' => 'Configurar Fiscal'],
            'sale-orders.fiscal.request' => ['module' => 'sale-orders', 'action' => 'fiscal.request', 'resource' => 'sale-orders', 'name' => 'Solicitar NF-e'],
            'discount-approvals.index' => ['module' => 'discount-approvals', 'action' => 'index', 'resource' => 'discount-approvals', 'name' => 'Visualizar Aprovações de Desconto'],
            'discount-approvals.approve' => ['module' => 'discount-approvals', 'action' => 'approve', 'resource' => 'discount-approvals', 'name' => 'Aprovar/Rejeitar Descontos'],
        ] as $slug => $meta) {
            $permissions[] = [
                'name' => $meta['name'],
                'slug' => $slug,
                'description' => $meta['name'],
                'module' => $meta['module'],
                'action' => $meta['action'],
                'resource' => $meta['resource'],
                'is_active' => true,
            ];
        }

        return $permissions;
    }
}
