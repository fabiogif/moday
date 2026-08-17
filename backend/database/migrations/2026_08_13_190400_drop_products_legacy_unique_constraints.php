<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * `products.name` e `products.flag` são unique global desde
 * 2024_04_24_023335_create_products_table. A versão pretendida em
 * 2025_11_09_000001_create_core_tables não tem unique em nenhum dos dois
 * (flag nem existe lá — foi só um artefato da migration antiga). Isso
 * impedia recriar um produto com o mesmo nome depois de soft-delete, um
 * fluxo de negócio válido (ex: reexcluir/recadastrar após exclusão via API).
 */
return new class extends Migration
{
    public function up(): void
    {
        foreach (['name', 'flag'] as $column) {
            $legacyUnique = collect(Schema::getIndexes('products'))
                ->first(fn ($index) => $index['unique'] && !$index['primary'] && $index['columns'] === [$column]);

            if ($legacyUnique) {
                Schema::table('products', function (Blueprint $table) use ($legacyUnique) {
                    $table->dropUnique($legacyUnique['name']);
                });
            }
        }
    }

    public function down(): void
    {
        // Intencionalmente não reverte: dados existentes podem já ter nomes
        // duplicados entre um produto ativo e um soft-deletado.
    }
};
