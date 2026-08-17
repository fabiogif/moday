<?php

namespace App\Reports\Builders;

use App\Reports\Contracts\ReportBuilderInterface;
use App\Reports\Formatters\DataFormatter;

abstract class AbstractReportBuilder implements ReportBuilderInterface
{
    protected array $filters = [];
    protected DataFormatter $formatter;
    
    public function __construct()
    {
        $this->formatter = new DataFormatter();
    }
    
    public function setFilters(array $filters): self
    {
        $this->filters = $this->sanitizeFilters($filters);
        return $this;
    }
    
    public function getMetadata(): array
    {
        return [
            'generated_at' => now()->toDateTimeString(),
            'filters' => $this->filters,
            'total_records' => count($this->build()),
        ];
    }
    
    /**
     * Sanitiza e valida os filtros
     */
    protected function sanitizeFilters(array $filters): array
    {
        return array_filter($filters, fn($value) => !is_null($value) && $value !== '');
    }
    
    /**
     * Formata uma linha de dados
     */
    protected function formatRow(array $row): array
    {
        return array_map(function ($value) {
            if (is_numeric($value)) {
                return $this->formatter->formatNumber($value);
            }
            return $value;
        }, $row);
    }
    
    abstract public function build(): array;
    abstract public function getColumns(): array;
    abstract public function getTitle(): string;
}

