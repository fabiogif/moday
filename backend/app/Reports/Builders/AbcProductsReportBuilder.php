<?php

namespace App\Reports\Builders;

use App\Reports\Formatters\CurrencyFormatter;
use App\Services\DistribtecReportService;

class AbcProductsReportBuilder extends AbstractReportBuilder
{
    public function __construct(
        private DistribtecReportService $service,
        private CurrencyFormatter $currencyFormatter
    ) {
        parent::__construct();
    }

    public function build(): array
    {
        $data = $this->service->abcProducts($this->filters);

        return collect($data['rows'])->map(fn ($row) => [
            $row['product'],
            $this->formatter->formatNumber($row['total_quantity'], 3),
            $this->currencyFormatter->format($row['total_value']),
            $this->formatter->formatPercentage($row['participation_percent']),
            $this->formatter->formatPercentage($row['cumulative_percent']),
            $row['abc_class'],
        ])->toArray();
    }

    public function getColumns(): array
    {
        return ['Produto', 'Qtd. Vendida', 'Valor Total', 'Participação %', 'Acumulado %', 'Classe ABC'];
    }

    public function getTitle(): string
    {
        return 'Curva ABC de Produtos';
    }
}
