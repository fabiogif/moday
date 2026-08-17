<?php

namespace App\Repositories;

use App\Models\Category;
use App\Models\Client;
use App\Models\Order;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\Table;
use App\Repositories\Contracts\DeletionGuardRepositoryInterface;
use Illuminate\Support\Facades\Schema;

class DeletionGuardRepository implements DeletionGuardRepositoryInterface
{
    public function getPreparingOrderIdentifiesForProduct(int $productId): array
    {
        return Order::where('status', 'Em Preparo')
            ->whereHas('products', function ($q) use ($productId) {
                $q->where('products.id', $productId);
            })
            ->pluck('identify')
            ->all();
    }

    public function getActiveProductNamesForCategory(string $categoryUuid, ?int $tenantId = null): array
    {
        $categoryQuery = Category::query()->where('uuid', $categoryUuid);
        if ($tenantId) {
            $categoryQuery->where('tenant_id', $tenantId);
        }
        $category = $categoryQuery->first();

        if (!$category) {
            return [];
        }

        return $category->products()
            ->where('is_active', true)
            ->pluck('name')
            ->all();
    }

    public function getPreparingOrderIdentifiesForClient(int $clientId): array
    {
        return Order::where('client_id', $clientId)
            ->where('status', 'Em Preparo')
            ->pluck('identify')
            ->all();
    }

    public function getPreparingOrderIdentifiesForTable(string $tableUuid): array
    {
        $table = Table::where('uuid', $tableUuid)->first();
        if (!$table) {
            return [];
        }

        return Order::where('table_id', $table->id)
            ->where('status', 'Em Preparo')
            ->pluck('identify')
            ->all();
    }

    public function getActiveOrderIdentifiesForEntity(mixed $entidadeId, string $tipoEntidade, ?int $tenantId = null): array
    {
        $query = Order::query()->where('status', '!=', Order::STATUS_ARCHIVED);

        if (Schema::hasColumn((new Order())->getTable(), 'archived_at')) {
            $query->whereNull('archived_at');
        }

        if ($tenantId) {
            $query->where('tenant_id', $tenantId);
        }

        switch ($tipoEntidade) {
            case 'cliente':
                $query->where('client_id', $entidadeId);
                break;
            case 'mesa':
                $table = Table::where('uuid', $entidadeId)->first();
                if (!$table) {
                    return [];
                }
                $query->where('table_id', $table->id);
                break;
            case 'produto':
                $query->whereHas('products', function ($q) use ($entidadeId) {
                    $q->where('products.id', $entidadeId);
                });
                break;
            case 'forma_pagamento':
                $paymentMethod = PaymentMethod::where('uuid', $entidadeId)->first();
                if (!$paymentMethod) {
                    return [];
                }

                $query->where(function ($q) use ($paymentMethod) {
                    $q->where('payment_method_id', $paymentMethod->uuid)
                      ->orWhere('payment_method', $paymentMethod->name);
                });
                break;
            default:
                throw new \InvalidArgumentException("Tipo de entidade desconhecido: {$tipoEntidade}");
        }

        return $query->pluck('identify')->all();
    }
}
