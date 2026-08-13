<?php

namespace App\Reports\Builders;

use App\Reports\Queries\DailySalesReportQuery;
use App\Reports\Formatters\CurrencyFormatter;

class DailySalesReportBuilder extends AbstractReportBuilder
{
    public function __construct(
        private DailySalesReportQuery $query,
        private CurrencyFormatter $currencyFormatter
    ) {
        parent::__construct();
    }
    
    public function build(): array
    {
        $orders = $this->query->execute($this->filters);
        
        return $orders->map(function ($order) {
            return [
                $order->id,
                $this->formatter->formatDateTime($order->created_at),
                $order->client?->name ?? 'Cliente não informado',
                $order->table?->identify ?? ($order->is_delivery ? 'Delivery' : '-'),
                $order->products()->count(),
                $this->currencyFormatter->format($order->total ?? 0),
                $order->status,
                $order->payment_method ?? '-',
            ];
        })->toArray();
    }
    
    public function getColumns(): array
    {
        return [
            'ID',
            'Data/Hora',
            'Cliente',
            'Mesa',
            'Qtd. Itens',
            'Total',
            'Status',
            'Pagamento',
        ];
    }
    
    public function getTitle(): string
    {
        if (isset($this->filters['date'])) {
            return 'Relatório de Vendas - ' . $this->formatter->formatDate($this->filters['date']);
        }
        
        return 'Relatório de Vendas';
    }
    
    public function getMetadata(): array
    {
        $metadata = parent::getMetadata();
        $data = $this->query->execute($this->filters);
        
        $metadata['summary'] = [
            'total_sales' => $data->count(),
            'total_amount' => $this->currencyFormatter->format($data->sum('total')),
            'average_ticket' => $this->currencyFormatter->format($data->avg('total') ?? 0),
        ];
        
        return $metadata;
    }
}

