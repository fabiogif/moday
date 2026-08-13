<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class AuditLayersCommand extends Command
{
    protected $signature = 'audit:layers';

    protected $description = 'Audita aderência ao padrão Controller → Service → Repository → Interface';

    public function handle(): int
    {
        $script = base_path('scripts/audit-layer-architecture.php');

        if (!file_exists($script)) {
            $this->error('Script de auditoria não encontrado.');

            return self::FAILURE;
        }

        passthru(PHP_BINARY . ' ' . escapeshellarg($script), $exitCode);

        return $exitCode === 0 ? self::SUCCESS : self::FAILURE;
    }
}
