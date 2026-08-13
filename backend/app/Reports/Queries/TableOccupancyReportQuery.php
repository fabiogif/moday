<?php

namespace App\Reports\Queries;

use App\Models\Table;
use App\Models\Order;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class TableOccupancyReportQuery
{
    public function execute(array $filters): Collection
    {
        $query = Table::query();
        
        // Filtro por tenant
        if (isset($filters['tenant_id'])) {
            $query->where('tenant_id', $filters['tenant_id']);
        }
        
        // Filtro por status da mesa
        if (isset($filters['is_active'])) {
            $query->where('is_active', $filters['is_active']);
        }
        
        // Adiciona estatísticas de pedidos
        $query->withCount(['orders' => function($q) use ($filters) {
            $q->where('is_delivery', false); // Apenas pedidos presenciais
            
            if (isset($filters['start_date'])) {
                $q->whereDate('created_at', '>=', $filters['start_date']);
            }
            if (isset($filters['end_date'])) {
                $q->whereDate('created_at', '<=', $filters['end_date']);
            }
        }])
        ->withSum(['orders' => function($q) use ($filters) {
            $q->where('is_delivery', false);
            
            if (isset($filters['start_date'])) {
                $q->whereDate('created_at', '>=', $filters['start_date']);
            }
            if (isset($filters['end_date'])) {
                $q->whereDate('created_at', '<=', $filters['end_date']);
            }
        }], 'total')
        ->with(['orders' => function($q) use ($filters) {
            $q->where('is_delivery', false)
                ->select('id', 'table_id', 'created_at', 'total')
                ->orderBy('created_at', 'desc')
                ->limit(1); // Última utilização
            
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
    
    /**
     * Calcula a taxa de ocupação baseado no período
     */
    public function calculateOccupancyRate(int $ordersCount, array $filters): float
    {
        if (!isset($filters['start_date']) || !isset($filters['end_date'])) {
            return 0;
        }
        
        $start = \Carbon\Carbon::parse($filters['start_date']);
        $end = \Carbon\Carbon::parse($filters['end_date']);
        $days = $start->diffInDays($end) + 1;
        
        // Assumindo 3 turnos por dia (almoço, jantar, madrugada)
        $possibleServices = $days * 3;
        
        if ($possibleServices == 0) {
            return 0;
        }
        
        return ($ordersCount / $possibleServices) * 100;
    }
}

