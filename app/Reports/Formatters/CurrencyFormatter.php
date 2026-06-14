<?php

namespace App\Reports\Formatters;

class CurrencyFormatter
{
    private string $currency = 'BRL';
    private string $locale = 'pt_BR';
    
    public function format($value): string
    {
        if (is_null($value)) {
            return 'R$ 0,00';
        }
        
        return 'R$ ' . number_format($value, 2, ',', '.');
    }
    
    public function parseToFloat(string $formatted): float
    {
        // Remove "R$" e espaços
        $cleaned = str_replace(['R$', ' '], '', $formatted);
        
        // Converte vírgula para ponto
        $cleaned = str_replace(['.', ','], ['', '.'], $cleaned);
        
        return (float) $cleaned;
    }
    
    public function formatWithoutSymbol($value): string
    {
        if (is_null($value)) {
            return '0,00';
        }
        
        return number_format($value, 2, ',', '.');
    }
}

