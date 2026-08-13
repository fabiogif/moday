<?php

namespace App\Reports\Builders;

use App\Reports\Queries\TopProductsReportQuery;
use App\Reports\Formatters\CurrencyFormatter;

class TopProductsReportBuilder extends AbstractReportBuilder
{
    public function __construct(
        private TopProductsReportQuery $query,
        private CurrencyFormatter $currencyFormatter
    ) {
        parent::__construct();
    }
    
    public function build(): array
    {
        $products = $this->query->execute($this->filters);
        $totalValue = $products->sum('total_value');
        
        return $products->map(function ($product) use ($totalValue) {
            $participation = $totalValue > 0 
                ? ($product->total_value / $totalValue) * 100 
                : 0;
            
            return [
                $product->id,
                $product->name,
                '-', // Categoria (adicionar se necessário)
                $this->formatter->formatNumber($product->total_quantity, 0),
                $this->currencyFormatter->format($product->total_value),
                $this->formatter->formatPercentage($participation),
                $this->currencyFormatter->format($product->average_price),
                $product->last_sale ? $this->formatter->formatDate($product->last_sale) : '-',
            ];
        })->toArray();
    }
    
    public function getColumns(): array
    {
        return [
            'ID',
            'Produto',
            'Categoria',
            'Qtd. Vendida',
            'Valor Total',
            'Participação %',
            'Preço Médio',
            'Última Venda',
        ];
    }
    
    public function getTitle(): string
    {
        $limit = $this->filters['limit'] ?? 20;
        return "Top {$limit} Produtos Mais Vendidos";
    }
}

