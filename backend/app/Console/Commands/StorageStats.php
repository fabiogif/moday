<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use App\Services\FileUploadService;

class StorageStats extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'storage:stats 
                            {--disk=public : Disco para analisar}
                            {--detailed : Mostrar informações detalhadas}';

    /**
     * The console command description.
     */
    protected $description = 'Mostra estatísticas de uso do storage';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $disk = $this->option('disk');
        $detailed = $this->option('detailed');
        
        $this->info("📊 Estatísticas do Storage - Disco: {$disk}");
        $this->newLine();
        
        $fileUploadService = app(FileUploadService::class);
        $stats = $fileUploadService->getStorageStats($disk);
        
        // Estatísticas básicas
        $this->table(
            ['Métrica', 'Valor'],
            [
                ['Arquivos', number_format($stats['file_count'])],
                ['Tamanho Total', $this->formatBytes($stats['total_size'])],
                ['Tamanho (MB)', $stats['total_size_mb'] . ' MB'],
                ['Disco', $stats['disk']],
            ]
        );
        
        if ($detailed) {
            $this->showDetailedStats($disk);
        }
        
        // Verificar configuração do disco
        $this->showDiskConfiguration($disk);
    }
    
    /**
     * Mostrar estatísticas detalhadas
     */
    private function showDetailedStats(string $disk)
    {
        $this->newLine();
        $this->info("📁 Análise Detalhada:");
        
        try {
            $files = Storage::disk($disk)->allFiles();
            $fileTypes = [];
            $totalSize = 0;
            
            foreach ($files as $file) {
                $extension = pathinfo($file, PATHINFO_EXTENSION);
                $size = Storage::disk($disk)->size($file);
                
                if (!isset($fileTypes[$extension])) {
                    $fileTypes[$extension] = ['count' => 0, 'size' => 0];
                }
                
                $fileTypes[$extension]['count']++;
                $fileTypes[$extension]['size'] += $size;
                $totalSize += $size;
            }
            
            // Ordenar por tamanho
            uasort($fileTypes, function ($a, $b) {
                return $b['size'] <=> $a['size'];
            });
            
            $rows = [];
            foreach ($fileTypes as $ext => $data) {
                $rows[] = [
                    strtoupper($ext ?: 'sem extensão'),
                    number_format($data['count']),
                    $this->formatBytes($data['size']),
                    round(($data['size'] / $totalSize) * 100, 1) . '%'
                ];
            }
            
            $this->table(
                ['Tipo', 'Arquivos', 'Tamanho', 'Percentual'],
                $rows
            );
            
        } catch (\Exception $e) {
            $this->error("Erro ao obter estatísticas detalhadas: {$e->getMessage()}");
        }
    }
    
    /**
     * Mostrar configuração do disco
     */
    private function showDiskConfiguration(string $disk)
    {
        $this->newLine();
        $this->info("⚙️ Configuração do Disco:");
        
        $config = config("filesystems.disks.{$disk}");
        
        if (!$config) {
            $this->error("Disco '{$disk}' não encontrado na configuração");
            return;
        }
        
        $rows = [
            ['Driver', $config['driver'] ?? 'n/a'],
            ['Root', $config['root'] ?? 'n/a'],
            ['URL', $config['url'] ?? 'n/a'],
            ['Visibility', $config['visibility'] ?? 'n/a'],
            ['Throw', $config['throw'] ? 'Sim' : 'Não'],
        ];
        
        if ($config['driver'] === 's3') {
            $rows[] = ['Bucket', $config['bucket'] ?? 'n/a'];
            $rows[] = ['Region', $config['region'] ?? 'n/a'];
            $rows[] = ['Endpoint', $config['endpoint'] ?? 'n/a'];
        }
        
        $this->table(['Propriedade', 'Valor'], $rows);
    }
    
    /**
     * Formatar bytes em formato legível
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, 2) . ' ' . $units[$pow];
    }
}
