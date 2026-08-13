<?php

namespace App\Reports\Formatters;

use Carbon\Carbon;

class DataFormatter
{
    public function formatNumber($value, int $decimals = 2): string
    {
        if (is_null($value)) {
            return '-';
        }
        
        return number_format($value, $decimals, ',', '.');
    }
    
    public function formatDate($date, string $format = 'd/m/Y'): string
    {
        if (is_null($date)) {
            return '-';
        }
        
        if (is_string($date)) {
            $date = Carbon::parse($date);
        }
        
        return $date->format($format);
    }
    
    public function formatDateTime($date): string
    {
        return $this->formatDate($date, 'd/m/Y H:i:s');
    }
    
    public function formatBoolean($value): string
    {
        return $value ? 'Sim' : 'Não';
    }
    
    public function formatPercentage($value, int $decimals = 2): string
    {
        if (is_null($value)) {
            return '-';
        }
        
        return $this->formatNumber($value, $decimals) . '%';
    }
}

