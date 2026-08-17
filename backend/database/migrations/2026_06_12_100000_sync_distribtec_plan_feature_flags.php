<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Sincroniza flags de feature em planos pagos.
 * Colunas podem ausentar se plans veio da migration 0000 mínima — ver create_core_tables.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('plans')) {
            return;
        }

        $paidSlugs = [
            'plano-basico',
            'plano-profissional',
            'plano-enterprise',
            'basico',
            'premium',
        ];

        $payload = [];
        foreach (['has_reports', 'has_marketing', 'has_order_completion_email'] as $column) {
            if (Schema::hasColumn('plans', $column)) {
                $payload[$column] = true;
            }
        }

        if ($payload === []) {
            return;
        }

        DB::table('plans')
            ->where(function ($query) use ($paidSlugs) {
                $query->whereIn('url', $paidSlugs)
                    ->orWhere('price', '>', 0);
            })
            ->update($payload);
    }

    public function down(): void
    {
        // Sem rollback automático — flags podem ter sido ajustadas manualmente no admin.
    }
};
