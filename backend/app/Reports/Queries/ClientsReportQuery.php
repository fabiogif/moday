<?php

namespace App\Reports\Queries;

use App\Models\Client;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ClientsReportQuery
{
    public function execute(array $filters): Collection
    {
        $query = Client::query();
        
        // Filtro por tenant
        if (isset($filters['tenant_id'])) {
            $query->where('tenant_id', $filters['tenant_id']);
        }
        
        // Filtro por status
        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }
        
        // Filtro por cidade
        if (isset($filters['city'])) {
            $query->where('city', $filters['city']);
        }
        
        // Filtro por estado
        if (isset($filters['state'])) {
            $query->where('state', $filters['state']);
        }
        
        // Adiciona estatísticas de pedidos
        $query->withCount('orders')
            ->withSum('orders', 'total')
            ->with(['orders' => function($q) use ($filters) {
                if (isset($filters['start_date'])) {
                    $q->whereDate('created_at', '>=', $filters['start_date']);
                }
                if (isset($filters['end_date'])) {
                    $q->whereDate('created_at', '<=', $filters['end_date']);
                }
            }]);
        
        return $query
            ->orderBy('orders_count', 'desc')
            ->get();
    }
}

