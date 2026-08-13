<?php

namespace App\Reports\Builders;

use App\Reports\Formatters\CurrencyFormatter;
use App\Services\DistribtecReportService;

class SalesByClientReportBuilder extends AbstractReportBuilder
{
    public function __construct(
        private DistribtecReportService $service,
        private CurrencyFormatter $currencyFormatter
    ) {
        parent::__construct();
    }

    public function build(): array
    {
        $data = $this->service->salesByClient($this->filters);

        return collect($data['rows'])->map(fn ($row) => [
            $row['client'],
            $row['orders_count'],
            $this->currencyFormatter->format($row['total_value']),
            $this->currencyFormatter->format($row['avg_ticket']),
        ])->toArray();
    }

    public function getColumns(): array
    {
        return ['Cliente', 'Pedidos', 'Valor Total', 'Ticket Médio'];
    }

    public function getTitle(): string
    {
        return 'Vendas por Cliente';
    }
}
