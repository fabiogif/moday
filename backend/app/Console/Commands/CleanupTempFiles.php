<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class CleanupTempFiles extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'files:cleanup-temp 
                            {--days=7 : Número de dias para manter arquivos temporários}
                            {--dry-run : Apenas mostrar o que seria deletado}';

    /**
     * The console command description.
     */
    protected $description = 'Limpa arquivos temporários antigos';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = $this->option('days');
        $dryRun = $this->option('dry-run');
        
        $this->info("Limpando arquivos temporários mais antigos que {$days} dias...");
        
        if ($dryRun) {
            $this->warn("MODO DRY-RUN: Nenhum arquivo será deletado");
        }
        
        $deletedCount = 0;
        $deletedSize = 0;
        
        try {
            // Limpar arquivos do disco temp
            $tempFiles = Storage::disk('temp')->allFiles();
            $cutoffDate = Carbon::now()->subDays($days);
            
            foreach ($tempFiles as $file) {
                $lastModified = Carbon::createFromTimestamp(
                    Storage::disk('temp')->lastModified($file)
                );
                
                if ($lastModified->lt($cutoffDate)) {
                    $fileSize = Storage::disk('temp')->size($file);
                    $deletedSize += $fileSize;
                    
                    if (!$dryRun) {
                        Storage::disk('temp')->delete($file);
                    }
                    
                    $deletedCount++;
                    $this->line("Arquivo: {$file} (modificado em: {$lastModified->format('Y-m-d H:i:s')})");
                }
            }
            
            // Limpar arquivos órfãos do storage local (se não estiver usando S3)
            if (config('filesystems.disks.public.driver') === 'local') {
                $this->cleanupOrphanedFiles($cutoffDate, $dryRun, $deletedCount, $deletedSize);
            }
            
            $deletedSizeMB = round($deletedSize / 1024 / 1024, 2);
            
            if ($dryRun) {
                $this->info("DRY-RUN: {$deletedCount} arquivos seriam deletados ({$deletedSizeMB} MB)");
            } else {
                $this->info("✅ {$deletedCount} arquivos deletados ({$deletedSizeMB} MB)");
            }
            
        } catch (\Exception $e) {
            $this->error("Erro durante limpeza: {$e->getMessage()}");
            return 1;
        }
        
        return 0;
    }
    
    /**
     * Limpar arquivos órfãos (sem referência no banco)
     */
    private function cleanupOrphanedFiles(Carbon $cutoffDate, bool $dryRun, int &$deletedCount, int &$deletedSize)
    {
        $this->info("Verificando arquivos órfãos...");
        
        // Buscar todas as imagens de produtos no banco
        $productImages = \App\Models\Product::whereNotNull('image')
            ->pluck('image')
            ->map(function ($url) {
                // Extrair path do URL
                $parsed = parse_url($url);
                return ltrim($parsed['path'] ?? '', '/');
            })
            ->filter()
            ->toArray();
        
        // Buscar todos os logos de empresas no banco
        $tenantLogos = \App\Models\Tenant::whereNotNull('logo')
            ->pluck('logo')
            ->map(function ($url) {
                $parsed = parse_url($url);
                return ltrim($parsed['path'] ?? '', '/');
            })
            ->filter()
            ->toArray();
        
        $usedFiles = array_merge($productImages, $tenantLogos);
        
        // Verificar arquivos no storage
        $allFiles = Storage::disk('public')->allFiles();
        
        foreach ($allFiles as $file) {
            // Pular se arquivo está sendo usado
            if (in_array($file, $usedFiles)) {
                continue;
            }
            
            $lastModified = Carbon::createFromTimestamp(
                Storage::disk('public')->lastModified($file)
            );
            
            if ($lastModified->lt($cutoffDate)) {
                $fileSize = Storage::disk('public')->size($file);
                $deletedSize += $fileSize;
                
                if (!$dryRun) {
                    Storage::disk('public')->delete($file);
                }
                
                $deletedCount++;
                $this->line("Arquivo órfão: {$file} (modificado em: {$lastModified->format('Y-m-d H:i:s')})");
            }
        }
    }
}
