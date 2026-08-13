<?php

namespace App\Reports\Builders;

use App\Reports\Queries\TableOccupancyReportQuery;
use App\Reports\Formatters\CurrencyFormatter;

class TableOccupancyReportBuilder extends AbstractReportBuilder
{
    public function __construct(
        private TableOccupancyReportQuery $query,
        private CurrencyFormatter $currencyFormatter
    ) {
        parent::__construct();
    }
    
    public function build(): array
    {
        $tables = $this->query->execute($this->filters);
        
        return $tables->map(function ($table) {
            $ordersCount = $table->orders_count ?? 0;
            $totalRevenue = $table->orders_sum_total ?? 0;
            $lastOrder = $table->orders->first();
            $occupancyRate = $this->query->calculateOccupancyRate($ordersCount, $this->filters);
            
            return [
                $table->identify,
                $table->capacity ?? '-',
                $this->formatter->formatNumber($ordersCount, 0),
                '-', // Tempo médio (calcular se necessário)
                $this->currencyFormatter->format($totalRevenue),
                $this->formatter->formatPercentage($occupancyRate),
                $lastOrder ? $this->formatter->formatDateTime($lastOrder->created_at) : '-',
            ];
        })->toArray();
    }
    
    public function getColumns(): array
    {
        return [
            'Mesa',
            'Capacidade',
            'Total Atendimentos',
            'Tempo Médio',
            'Faturamento',
            'Taxa Ocupação %',
            'Último Atendimento',
        ];
    }
    
    public function getTitle(): string
    {
        return 'Relatório de Ocupação de Mesas';
    }
    
    public function getMetadata(): array
    {
        $metadata = parent::getMetadata();
        $tables = $this->query->execute($this->filters);
        
        $metadata['summary'] = [
            'total_tables' => $tables->count(),
            'total_services' => $tables->sum('orders_count'),
            'total_revenue' => $this->currencyFormatter->format($tables->sum('orders_sum_total')),
            'average_occupancy' => $this->formatter->formatPercentage(
                $tables->avg(function($table) {
                    return $this->query->calculateOccupancyRate($table->orders_count ?? 0, $this->filters);
                }) ?? 0
            ),
        ];
        
        return $metadata;
    }
}

