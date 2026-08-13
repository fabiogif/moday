<?php

namespace App\Reports\Queries;

use App\Models\Order;
use Illuminate\Support\Collection;

class DailySalesReportQuery
{
    public function execute(array $filters): Collection
    {
        $query = Order::query();
        
        // Filtro por tenant
        if (isset($filters['tenant_id'])) {
            $query->where('tenant_id', $filters['tenant_id']);
        }
        
        // Filtro por data
        if (isset($filters['date'])) {
            $query->whereDate('created_at', $filters['date']);
        }
        
        // Filtro por período
        if (isset($filters['start_date'])) {
            $query->whereDate('created_at', '>=', $filters['start_date']);
        }
        
        if (isset($filters['end_date'])) {
            $query->whereDate('created_at', '<=', $filters['end_date']);
        }
        
        // Filtro por cliente
        if (isset($filters['client_id'])) {
            $query->where('client_id', $filters['client_id']);
        }
        
        // Filtro por mesa
        if (isset($filters['table_id'])) {
            $query->where('table_id', $filters['table_id']);
        }
        
        // Filtro por status
        if (isset($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        
        // Filtro por método de pagamento
        if (isset($filters['payment_method'])) {
            $query->where('payment_method', $filters['payment_method']);
        }
        
        return $query
            ->with(['client', 'table'])
            ->orderBy('created_at', 'desc')
            ->get();
    }
}

