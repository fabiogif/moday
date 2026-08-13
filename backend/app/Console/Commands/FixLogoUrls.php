<?php

namespace App\Console\Commands;

use App\Helpers\ImageHelper;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\Tenant;
use Illuminate\Console\Command;

class FixLogoUrls extends Command
{
    protected $signature = 'fix:media-urls {--dry-run : Apenas exibir alterações sem salvar}';

    protected $description = 'Normaliza URLs de mídia (localhost, barras duplas) para paths relativos no storage';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        if ($dryRun) {
            $this->warn('Modo dry-run: nenhuma alteração será persistida.');
        }

        $this->info('Normalizando URLs de mídia...');

        $tenantCount = $this->fixModelUrls(Tenant::class, 'logo', 'tenant', $dryRun);
        $productCount = $this->fixModelUrls(Product::class, 'image', 'product', $dryRun);
        $couponCount = $this->fixModelUrls(Coupon::class, 'image', 'coupon', $dryRun);

        $total = $tenantCount + $productCount + $couponCount;

        $this->info("✅ Concluído. {$total} registro(s) normalizado(s).");

        return Command::SUCCESS;
    }

    /**
     * @param class-string $modelClass
     */
    private function fixModelUrls(string $modelClass, string $column, string $label, bool $dryRun): int
    {
        $count = 0;

        $modelClass::query()
            ->whereNotNull($column)
            ->where($column, '!=', '')
            ->cursor()
            ->each(function ($model) use ($column, $label, $dryRun, &$count) {
                $oldValue = $model->{$column};
                $newValue = ImageHelper::normalizeToStoragePath($oldValue);

                if (!$newValue || $newValue === $oldValue) {
                    return;
                }

                $this->line("✓ {$label} #{$model->id}: {$oldValue} → {$newValue}");
                $count++;

                if (!$dryRun) {
                    $model->{$column} = $newValue;
                    $model->save();
                }
            });

        $this->info("  {$label}: {$count} registro(s)");

        return $count;
    }
}
