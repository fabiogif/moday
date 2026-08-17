<?php

namespace App\Reports\Builders;

use App\Reports\Queries\ClientsReportQuery;
use App\Reports\Formatters\CurrencyFormatter;

class ClientsReportBuilder extends AbstractReportBuilder
{
    public function __construct(
        private ClientsReportQuery $query,
        private CurrencyFormatter $currencyFormatter
    ) {
        parent::__construct();
    }
    
    public function build(): array
    {
        $clients = $this->query->execute($this->filters);
        
        return $clients->map(function ($client) {
            $lastOrder = $client->orders->sortByDesc('created_at')->first();
            $totalSpent = $client->orders_sum_total ?? 0;
            $ordersCount = $client->orders_count ?? 0;
            $avgTicket = $ordersCount > 0 ? $totalSpent / $ordersCount : 0;
            
            return [
                $client->id,
                $client->name,
                $client->cpf ?? '-',
                $client->email ?? '-',
                $client->phone ?? '-',
                $ordersCount,
                $this->currencyFormatter->format($totalSpent),
                $this->currencyFormatter->format($avgTicket),
                $lastOrder ? $this->formatter->formatDate($lastOrder->created_at) : '-',
                $client->is_active ? 'Ativo' : 'Inativo',
            ];
        })->toArray();
    }
    
    public function getColumns(): array
    {
        return [
            'ID',
            'Nome',
            'CPF',
            'Email',
            'Telefone',
            'Total Pedidos',
            'Valor Total',
            'Ticket Médio',
            'Última Compra',
            'Status',
        ];
    }
    
    public function getTitle(): string
    {
        return 'Relatório de Clientes';
    }
}

