<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $paidSlugs = [
            'plano-basico',
            'plano-profissional',
            'plano-enterprise',
            'basico',
            'premium',
        ];

        DB::table('plans')
            ->where(function ($query) use ($paidSlugs) {
                $query->whereIn('url', $paidSlugs)
                    ->orWhere('price', '>', 0);
            })
            ->update([
                'has_reports' => true,
                'has_marketing' => true,
                'has_order_completion_email' => true,
            ]);
    }

    public function down(): void
    {
        // Sem rollback automático — flags podem ter sido ajustadas manualmente no admin.
    }
};
