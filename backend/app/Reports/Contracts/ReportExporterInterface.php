<?php

namespace App\Reports\Contracts;

interface ReportExporterInterface
{
    /**
     * Exporta o relatório e retorna o caminho do arquivo
     */
    public function export(array $data, array $columns, string $filename): string;
    
    /**
     * Retorna a extensão do arquivo
     */
    public function getExtension(): string;
    
    /**
     * Retorna o tipo MIME
     */
    public function getMimeType(): string;
}

