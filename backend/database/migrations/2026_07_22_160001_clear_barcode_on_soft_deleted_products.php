<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Soft-deleted products still held barcodes under UNIQUE(tenant_id, barcode),
     * blocking re-registration of the same code. Clear them once.
     */
    public function up(): void
    {
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
