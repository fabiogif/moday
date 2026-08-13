<?php

namespace App\Reports\Builders;

use App\Reports\Queries\MonthlyFinancialReportQuery;
use App\Reports\Formatters\CurrencyFormatter;
use Carbon\Carbon;

class MonthlyFinancialReportBuilder extends AbstractReportBuilder
{
    public function __construct(
        private MonthlyFinancialReportQuery $query,
        private CurrencyFormatter $currencyFormatter
    ) {
        parent::__construct();
    }
    
    public function build(): array
    {
        $dailyData = $this->query->execute($this->filters);
        
        return $dailyData->map(function ($day) {
            $mainPaymentMethod = $this->extractMainPaymentMethod($day->payment_methods);
            
            return [
                $day->day,
                $this->formatter->formatDate($day->date),
                $this->formatter->formatNumber($day->sales_count, 0),
                $this->currencyFormatter->format($day->gross_revenue),
                $this->currencyFormatter->format($day->average_ticket),
                $mainPaymentMethod,
            ];
        })->toArray();
    }
    
    public function getColumns(): array
    {
        return [
            'Dia',
            'Data',
            'Qtd. Vendas',
            'Receita Bruta',
            'Ticket Médio',
            'Pgto Principal',
        ];
    }
    
    public function getTitle(): string
    {
        $month = $this->filters['month'] ?? now()->month;
        $year = $this->filters['year'] ?? now()->year;
        $monthName = Carbon::create($year, $month)->locale('pt_BR')->monthName;
        
        return "Relatório Financeiro - " . ucfirst($monthName) . "/{$year}";
    }
    
    public function getMetadata(): array
    {
        $metadata = parent::getMetadata();
        $dailyData = $this->query->execute($this->filters);
        $previousTotal = $this->query->getPreviousMonthTotal($this->filters);
        
        $totalRevenue = $dailyData->sum('gross_revenue');
        $growth = $previousTotal > 0 
            ? (($totalRevenue - $previousTotal) / $previousTotal) * 100 
            : 0;
        
        $metadata['summary'] = [
            'total_sales' => $dailyData->sum('sales_count'),
            'gross_revenue' => $this->currencyFormatter->format($totalRevenue),
            'average_ticket' => $this->currencyFormatter->format($dailyData->avg('average_ticket') ?? 0),
            'growth_percentage' => $this->formatter->formatPercentage($growth),
        ];
        
        return $metadata;
    }
    
    private function extractMainPaymentMethod(?string $paymentMethods): string
    {
        if (!$paymentMethods) {
            return '-';
        }
        
        $methods = explode(',', $paymentMethods);
        $counts = array_count_values($methods);
        arsort($counts);
        
        return array_key_first($counts) ?? '-';
    }
}

