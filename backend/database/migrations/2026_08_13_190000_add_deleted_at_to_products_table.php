<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `products` foi criada por 2024_04_24_023335_create_products_table (sem
 * soft delete). A migration 2025_11_09_000001_create_core_tables, que tem
 * `$table->softDeletes()` na definição de `products`, roda dentro de
 * `if (!Schema::hasTable('products'))` — como a tabela já existe, esse
 * bloco inteiro é pulado e `deleted_at` nunca é criada. Isso quebra toda
 * rota que usa SoftDeletes em Product (ex: paginação com
 * `withTrashed`/`whereNull('deleted_at')` implícito do Eloquent).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('products', 'deleted_at')) {
            Schema::table('products', function (Blueprint $table) {
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('products', 'deleted_at')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }
    }
};
