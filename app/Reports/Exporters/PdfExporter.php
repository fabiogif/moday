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

        $dir = dirname($path);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $html = view('pdfs.reports.table', [
            'title' => $filename,
            'generatedAt' => now()->format('d/m/Y H:i:s'),
            'columns' => $columns,
            'rows' => $data,
        ])->render();

        Pdf::loadHTML($html)
            ->setPaper('A4', 'landscape')
            ->save($path);

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
}
