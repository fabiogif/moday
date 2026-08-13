<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Mesmo bug de 2026_08_13_190000_add_deleted_at_to_products_table: `optionals`
 * só existe na definição de `products` dentro do bloco
 * `if (!Schema::hasTable('products'))` de 2025_11_09_000001_create_core_tables,
 * que nunca roda porque a tabela já existe desde 2024_04_24. Nenhuma outra
 * migration cria essa coluna (a de 2025_01_28 só cobre shipping_info,
 * warehouse_location e variations).
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('products', 'optionals')) {
            Schema::table('products', function (Blueprint $table) {
                $table->json('optionals')->nullable();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('products', 'optionals')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropColumn('optionals');
            });
        }
    }
};
