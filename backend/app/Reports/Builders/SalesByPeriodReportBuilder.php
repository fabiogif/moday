<?php

namespace App\Reports\Builders;

use App\Reports\Formatters\CurrencyFormatter;
use App\Services\DistribtecReportService;

class SalesByPeriodReportBuilder extends AbstractReportBuilder
{
    public function __construct(
        private DistribtecReportService $service,
        private CurrencyFormatter $currencyFormatter
    ) {
        parent::__construct();
    }

    public function build(): array
    {
        $data = $this->service->salesByPeriod($this->filters);

        return collect($data['rows'])->map(fn ($row) => [
            $row['identify'],
            $row['client'],
            $row['ordered_at'],
            $row['status'],
            $this->currencyFormatter->format($row['subtotal']),
            $this->currencyFormatter->format($row['discount_amount']),
            $this->currencyFormatter->format($row['total']),
        ])->toArray();
    }

    public function getColumns(): array
    {
        return ['PV', 'Cliente', 'Data', 'Status', 'Subtotal', 'Desconto', 'Total'];
    }

    public function getTitle(): string
    {
        return 'Vendas por Período';
    }
}
