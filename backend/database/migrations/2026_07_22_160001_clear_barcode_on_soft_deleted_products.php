<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Soft-deleted products still held barcodes under UNIQUE(tenant_id, barcode),
     * blocking re-registration of the same code. Clear them once.
     *
     * Idempotente: em produção `deleted_at` só chega em 2026_08_13_190000
     * (create_core_tables pulou o bloco de products). Sem a coluna, este
     * update quebra o migrate — e não há soft-deletes para limpar ainda.
     */
    public function up(): void
    {
        if (
            !Schema::hasTable('products')
            || !Schema::hasColumn('products', 'deleted_at')
            || !Schema::hasColumn('products', 'barcode')
        ) {
            return;
        }

        DB::table('products')
            ->whereNotNull('deleted_at')
            ->whereNotNull('barcode')
            ->update(['barcode' => null]);
    }

    public function down(): void
    {
        // Irreversível: barcodes de produtos soft-deleted não são recuperados.
    }
};
