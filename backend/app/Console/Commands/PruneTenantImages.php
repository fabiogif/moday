<?php

namespace App\Console\Commands;

use App\Helpers\ImageHelper;
use App\Models\Tenant;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Logos e capas antigos ficavam no storage porque a troca/remoção apagava no disco errado.
 * Lista (e, com --delete, apaga) arquivos de logos/capas que nenhum tenant referencia.
 */
class PruneTenantImages extends Command
{
    protected $signature = 'files:prune-tenant-images
                            {--delete : Apaga os arquivos listados (sem isso, só relata)}
                            {--min-age=24 : Ignora arquivos modificados nas últimas N horas (upload em andamento)}';

    protected $description = 'Relata ou apaga logos e capas de tenants que não são mais usados';

    public function handle(): int
    {
        $disk = Storage::disk('logos');
        $delete = (bool) $this->option('delete');
        $cutoff = Carbon::now()->subHours((int) $this->option('min-age'));

        $referenced = Tenant::query()
            ->get(['logo', 'cover'])
            ->flatMap(fn (Tenant $tenant) => [$tenant->logo, $tenant->cover])
            ->map(fn (?string $image) => ImageHelper::normalizeToStoragePath($image))
            ->filter()
            ->flip();

        $orphans = collect($disk->allFiles('tenants'))
            ->filter(fn (string $path) => preg_match('#^tenants/[^/]+/(logos|covers)/#', $path))
            ->reject(fn (string $path) => $referenced->has($path))
            ->filter(fn (string $path) => Carbon::createFromTimestamp($disk->lastModified($path))->lt($cutoff))
            ->values();

        if ($orphans->isEmpty()) {
            $this->info('Nenhuma imagem de tenant sem uso.');

            return self::SUCCESS;
        }

        $totalBytes = 0;
        foreach ($orphans as $path) {
            $size = $disk->size($path);
            $totalBytes += $size;
            $this->line(sprintf('%s (%s KB)', $path, number_format($size / 1024, 1, ',', '.')));
        }

        $summary = sprintf('%d arquivo(s), %s MB', $orphans->count(), number_format($totalBytes / 1048576, 2, ',', '.'));

        if (!$delete) {
            $this->warn("Sem uso: {$summary}. Nada foi apagado — rode com --delete para apagar.");

            return self::SUCCESS;
        }

        $disk->delete($orphans->all());
        $this->info("Apagados: {$summary}.");

        return self::SUCCESS;
    }
}
