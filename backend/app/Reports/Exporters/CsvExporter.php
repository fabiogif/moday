<?php

namespace App\Reports\Exporters;

use App\Reports\Contracts\ReportExporterInterface;

class CsvExporter implements ReportExporterInterface
{
    public function export(array $data, array $columns, string $filename): string
    {
        $filename = $this->sanitizeFilename($filename);
        $path = storage_path('app/public/reports/' . $filename . '.' . $this->getExtension());
        
        // Cria o diretório se não existir
        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }
        
        $file = fopen($path, 'w');
        
        // Adiciona BOM para UTF-8
        fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
        
        // Adiciona cabeçalho
        fputcsv($file, $columns, ';');
        
        // Adiciona dados
        foreach ($data as $row) {
            fputcsv($file, $row, ';');
        }
        
        fclose($file);
        
        return $path;
    }
    
    public function getExtension(): string
    {
        return 'csv';
    }
    
    public function getMimeType(): string
    {
        return 'text/csv';
    }
    
    private function sanitizeFilename(string $filename): string
    {
        $filename = preg_replace('/[^A-Za-z0-9_\-\s]/', '_', $filename);
        return $filename . '_' . now()->format('Y-m-d_His');
    }
}

