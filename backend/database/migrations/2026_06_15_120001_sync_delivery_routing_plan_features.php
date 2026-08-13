<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;

return new class extends Migration
{
    public function up(): void
    {
        Artisan::call('db:seed', [
            '--class' => 'Database\\Seeders\\FeatureDefinitionsSeeder',
            '--force' => true,
        ]);
    }

    public function down(): void
    {
        // Não reverte flags de plano — dados de negócio.
    }
};
