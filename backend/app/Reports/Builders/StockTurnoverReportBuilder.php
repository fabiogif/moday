<?php

namespace App\Reports\Builders;

use App\Services\DistribtecReportService;

class StockTurnoverReportBuilder extends AbstractReportBuilder
{
    public function __construct(
        private DistribtecReportService $service
    ) {
        parent::__construct();
    }

    public function build(): array
    {
        $data = $this->service->stockTurnover($this->filters);

        return collect($data['rows'])->map(fn ($row) => [
            $row['product'],
            $this->formatter->formatNumber($row['current_stock'], 3),
            $this->formatter->formatNumber($row['entries'], 3),
            $this->formatter->formatNumber($row['exits'], 3),
            $this->formatter->formatNumber($row['turnover_index'], 2),
        ])->toArray();
    }

    public function getColumns(): array
    {
        return ['Produto', 'Estoque Atual', 'Entradas', 'Saídas', 'Índice de Giro'];
    }

    public function getTitle(): string
    {
        return 'Giro de Estoque';
    }
}
