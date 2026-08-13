<?php

namespace App\Services;

use App\Models\FinancialCategory;
use App\Models\Tenant;

class TenantFinancialCategoryProvisioner
{
    /**
     * @var array<int, array{name: string, type: string, applies_to_payable: bool, applies_to_receivable: bool, color: string}>
     */
    private const DEFAULT_CATEGORIES = [
        [
            'name' => 'Vendas',
            'type' => 'receita',
            'applies_to_payable' => false,
            'applies_to_receivable' => true,
            'color' => '#10b981',
        ],
        [
            'name' => 'Serviços',
            'type' => 'receita',
            'applies_to_payable' => false,
            'applies_to_receivable' => true,
            'color' => '#3b82f6',
        ],
        [
            'name' => 'Outras Receitas',
            'type' => 'receita',
            'applies_to_payable' => false,
            'applies_to_receivable' => true,
            'color' => '#06b6d4',
        ],
        [
            'name' => 'Fornecedores',
            'type' => 'despesa',
            'applies_to_payable' => true,
            'applies_to_receivable' => false,
            'color' => '#ef4444',
        ],
        [
            'name' => 'Aluguel',
            'type' => 'despesa',
            'applies_to_payable' => true,
            'applies_to_receivable' => false,
            'color' => '#f97316',
        ],
        [
            'name' => 'Salários',
            'type' => 'despesa',
            'applies_to_payable' => true,
            'applies_to_receivable' => false,
            'color' => '#8b5cf6',
        ],
        [
            'name' => 'Impostos',
            'type' => 'despesa',
            'applies_to_payable' => true,
            'applies_to_receivable' => false,
            'color' => '#6b7280',
        ],
        [
            'name' => 'Outras Despesas',
            'type' => 'despesa',
            'applies_to_payable' => true,
            'applies_to_receivable' => false,
            'color' => '#f59e0b',
        ],
    ];

    public function provision(Tenant $tenant): int
    {
        $created = 0;

        foreach (self::DEFAULT_CATEGORIES as $category) {
            $exists = FinancialCategory::query()
                ->where('tenant_id', $tenant->id)
                ->where('name', $category['name'])
                ->exists();

            if ($exists) {
                continue;
            }

            FinancialCategory::create(array_merge($category, [
                'tenant_id' => $tenant->id,
                'is_active' => true,
            ]));

            $created++;
        }

        return $created;
    }
}
