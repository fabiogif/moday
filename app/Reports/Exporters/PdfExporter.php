<?php

namespace App\Reports\Exporters;

use App\Reports\Contracts\ReportExporterInterface;
use Barryvdh\DomPDF\Facade\Pdf;

class PdfExporter implements ReportExporterInterface
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
        
        // Gera HTML para PDF
        $html = $this->generateHtml($data, $columns, $filename);
        
        // Gera PDF usando DomPDF
        $pdf = Pdf::loadHTML($html);
        $pdf->setPaper('A4', 'landscape'); // Paisagem para melhor visualização de tabelas
        $pdf->save($path);
        
        return $path;
    }
    
    public function getExtension(): string
    {
        return 'pdf';
    }
    
    public function getMimeType(): string
    {
        return 'application/pdf';
    }
    
    private function sanitizeFilename(string $filename): string
    {
        $filename = preg_replace('/[^A-Za-z0-9_\-\s]/', '_', $filename);
        return $filename . '_' . now()->format('Y-m-d_His');
    }
    
    private function generateHtml(array $data, array $columns, string $title): string
    {
        $html = '<!DOCTYPE html><html><head><meta charset="UTF-8">';
        $html .= '<title>' . htmlspecialchars($title) . '</title>';
        $html .= '<style>';
        $html .= 'body { font-family: DejaVu Sans, sans-serif; margin: 20px; font-size: 12px; }';
        $html .= 'h1 { color: #333; font-size: 18px; margin-bottom: 10px; }';
        $html .= '.info { color: #666; font-size: 10px; margin-bottom: 20px; }';
        $html .= 'table { width: 100%; border-collapse: collapse; margin-top: 10px; }';
        $html .= 'th, td { border: 1px solid #ddd; padding: 6px; text-align: left; font-size: 10px; }';
        $html .= 'th { background-color: #4CAF50; color: white; font-weight: bold; }';
        $html .= 'tr:nth-child(even) { background-color: #f9f9f9; }';
        $html .= '.footer { margin-top: 20px; text-align: center; color: #999; font-size: 9px; }';
        $html .= '</style></head><body>';
        
        $html .= '<h1>' . htmlspecialchars($title) . '</h1>';
        $html .= '<div class="info">Gerado em: ' . now()->format('d/m/Y H:i:s') . '</div>';
        
        $html .= '<table>';
        $html .= '<thead><tr>';
        foreach ($columns as $column) {
            $html .= '<th>' . htmlspecialchars($column) . '</th>';
        }
        $html .= '</tr></thead>';
        
        $html .= '<tbody>';
        foreach ($data as $row) {
            $html .= '<tr>';
            foreach ($row as $cell) {
                $html .= '<td>' . htmlspecialchars($cell ?? '-') . '</td>';
            }
            $html .= '</tr>';
        }
        $html .= '</tbody>';
        $html .= '</table>';
        
        $html .= '</body></html>';
        
        return $html;
    }
}

