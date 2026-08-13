<?php

namespace App\Services;

use App\Reports\Contracts\ReportBuilderInterface;
use App\Reports\Contracts\ReportExporterInterface;
use Illuminate\Support\Facades\Log;

class ReportService
{
    /**
     * Gera um relatório de forma síncrona
     */
    public function generate(
        ReportBuilderInterface $builder,
        ReportExporterInterface $exporter,
        array $filters = []
    ): string {
        try {
            Log::info('Gerando relatório', [
                'builder' => get_class($builder),
                'exporter' => get_class($exporter),
                'filters' => $filters,
            ]);
            
            // Constrói o relatório
            $data = $builder->setFilters($filters)->build();
            $columns = $builder->getColumns();
            $title = $builder->getTitle();
            
            // Exporta
            $filePath = $exporter->export($data, $columns, $title);
            
            Log::info('Relatório gerado com sucesso', ['path' => $filePath]);
            
            return $filePath;
        } catch (\Exception $e) {
            Log::error('Erro ao gerar relatório', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            throw $e;
        }
    }
    
    /**
     * Retorna o exporter baseado no formato
     */
    public function getExporter(string $format): ReportExporterInterface
    {
        return match($format) {
            'pdf' => app(\App\Reports\Exporters\PdfExporter::class),
            'excel' => app(\App\Reports\Exporters\ExcelExporter::class),
            'csv' => app(\App\Reports\Exporters\CsvExporter::class),
            default => throw new \InvalidArgumentException("Formato inválido: {$format}"),
        };
    }
    
    /**
     * Determina se o relatório deve ser processado de forma assíncrona
     */
    public function shouldProcessAsync(array $filters): bool
    {
        // Se o período for maior que 90 dias, processa em fila
        if (isset($filters['start_date']) && isset($filters['end_date'])) {
            $start = \Carbon\Carbon::parse($filters['start_date']);
            $end = \Carbon\Carbon::parse($filters['end_date']);
            
            if ($start->diffInDays($end) > 90) {
                return true;
            }
        }
        
        return false;
    }
}

