<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Profile;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class DefaultProfilesSeeder extends Seeder
{
    private const PROFILES = [
        'Administrador' => [
            'description' => 'Acesso total ao sistema — todas as permissões ativas.',
            'permissions' => '*',
        ],
        'Gerente Comercial' => [
            'description' => 'Vendas, clientes, preços, aprovações de desconto e visão de recebíveis.',
            'permissions' => [
                'sale-orders.index', 'sale-orders.store', 'sale-orders.update', 'sale-orders.destroy',
                'sale-orders.fiscal.request',
                'offer-rules.index', 'offer-rules.store', 'offer-rules.update', 'offer-rules.destroy',
                'clients.index', 'clients.show', 'clients.store', 'clients.update', 'clients.destroy',
                'price-tables.index', 'price-tables.store', 'price-tables.update', 'price-tables.destroy',
                'accounts-receivable.index',
                'discount-approvals.index', 'discount-approvals.approve',
                'suppliers.index',
                'products.index', 'products.show',
                'categories.index',
                'reports.index', 'reports.generate',
                'visits.index', 'visits.store', 'visits.update', 'visits.destroy',
                'visits.checkin', 'visits.checkout', 'visits.change-status', 'visits.media.store',
                'visits.recurrence.manage', 'visits.reports.index', 'visits.view-all',
            ],
        ],
        'Vendedor' => [
            'description' => 'Criar, visualizar e avançar o status de pedidos de venda e consultar preços.',
            'permissions' => [
                'sale-orders.index', 'sale-orders.store', 'sale-orders.update',
                'clients.index', 'clients.show', 'clients.store', 'clients.update',
                'price-tables.index',
                'products.index', 'products.show',
                'categories.index',
                'visits.index', 'visits.store', 'visits.update',
                'visits.checkin', 'visits.checkout', 'visits.change-status', 'visits.media.store',
            ],
        ],
        'Comprador' => [
            'description' => 'Pedidos de compra, fornecedores e recebimento de mercadorias.',
            'permissions' => [
                'purchase-orders.index', 'purchase-orders.store', 'purchase-orders.update', 'purchase-orders.destroy',
                'suppliers.index', 'suppliers.store', 'suppliers.update', 'suppliers.destroy',
                'batches.index', 'batches.store',
                'stock-movements.index', 'stock-movements.store',
                'warehouses.index',
                'products.index', 'products.show',
                'categories.index',
            ],
        ],
        'Estoquista' => [
            'description' => 'Controle de estoque, lotes, movimentações e inventário.',
            'permissions' => [
                'batches.index', 'batches.store', 'batches.update', 'batches.destroy',
                'batches.recall', 'batches.quarantine',
                'stock-movements.index', 'stock-movements.store',
                'warehouses.index', 'warehouses.store', 'warehouses.update', 'warehouses.destroy',
                'inventory-counts.index', 'inventory-counts.store', 'inventory-counts.update',
                'picking.confirm',
                'products.index', 'products.show',
                'categories.index',
            ],
        ],
        'Expedidor' => [
            'description' => 'Separação de pedidos, montagem de romaneios e despacho.',
            'permissions' => [
                'picking.confirm',
                'shipments.index', 'shipments.store',
                'shipments.dispatch',
                'sale-orders.index',
                'products.index', 'products.show',
            ],
        ],
        'Motorista' => [
            'description' => 'Visualizar romaneios atribuídos e confirmar entregas.',
            'permissions' => [
                'shipments.index',
                'shipments.deliver',
            ],
        ],
        'Financeiro' => [
            'description' => 'Contas a receber/pagar, fluxo de caixa, despesas e categorias financeiras.',
            'permissions' => [
                'accounts-receivable.index', 'accounts-receivable.store', 'accounts-receivable.update', 'accounts-receivable.destroy',
                'accounts-payable.index', 'accounts-payable.store', 'accounts-payable.update', 'accounts-payable.destroy',
                'financial-categories.index', 'financial-categories.store', 'financial-categories.update', 'financial-categories.destroy',
                'expenses.index', 'expenses.store', 'expenses.update', 'expenses.destroy', 'expenses.stats',
                'sale-orders.index',
                'suppliers.index',
                'discount-approvals.index',
                'reports.index', 'reports.generate',
                'clients.index',
            ],
        ],
        'Fiscal / Contábil' => [
            'description' => 'Emissão de NF-e, configuração fiscal e auditoria.',
            'permissions' => [
                'sale-orders.index',
                'sale-orders.fiscal.request',
                'settings.fiscal.update',
                'audit-logs.index',
                'accounts-receivable.index',
                'accounts-payable.index',
                'reports.index', 'reports.generate',
            ],
        ],
    ];

    public function run(?int $tenantId = null): void
    {
        $tenants = $tenantId
            ? Tenant::where('id', $tenantId)->get()
            : Tenant::all();

        if ($tenants->isEmpty()) {
            $this->command?->error('Nenhum tenant encontrado.');
            return;
        }

        foreach ($tenants as $tenant) {
            $this->seedForTenant($tenant);
        }
    }

    private function seedForTenant(Tenant $tenant): void
    {
        $allPermissions = Permission::where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->pluck('id', 'slug');

        foreach (self::PROFILES as $name => $config) {
            $profile = Profile::firstOrCreate(
                ['name' => $name, 'tenant_id' => $tenant->id],
                [
                    'description' => $config['description'],
                    'is_active' => true,
                ]
            );

            if ($config['permissions'] === '*') {
                $permissionIds = $allPermissions->values()->toArray();
            } else {
                $permissionIds = $allPermissions
                    ->filter(fn ($id, $slug) => in_array($slug, $config['permissions']))
                    ->values()
                    ->toArray();
            }

            $profile->permissions()->syncWithoutDetaching($permissionIds);

            $count = count($permissionIds);
            $status = $profile->wasRecentlyCreated ? 'criado' : 'atualizado';
            $this->command?->info("Tenant {$tenant->id} — perfil \"{$name}\" {$status} com {$count} permissões");
        }
    }
}
