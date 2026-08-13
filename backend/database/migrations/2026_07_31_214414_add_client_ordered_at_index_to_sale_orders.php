<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('sale_orders')) {
            return;
        }

        try {
            Schema::table('sale_orders', function (Blueprint $table) {
                $table->index(['client_id', 'ordered_at']);
            });
        } catch (\Throwable) {
            // Índice já existe ou colunas ausentes em schema legado
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('sale_orders')) {
            return;
        }

        try {
            Schema::table('sale_orders', function (Blueprint $table) {
                $table->dropIndex(['client_id', 'ordered_at']);
            });
        } catch (\Throwable) {
            // no-op
        }
    }
};
