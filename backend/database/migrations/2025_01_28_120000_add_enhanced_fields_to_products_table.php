<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Campos de produto aprimorados
            
            // Marca/Fabricante - já existe na migração original
            // SKU/Código do produto - já existe na migração original
            // Peso - já existe na migração original
            // Dimensões - já existem na migração original
            
            // Informações logísticas
            if (!Schema::hasColumn('products', 'shipping_info')) {
                $table->text('shipping_info')->nullable()->comment('Informações de envio')->after('description');
            }
            
            if (!Schema::hasColumn('products', 'warehouse_location')) {
                $table->string('warehouse_location')->nullable()->comment('Localização no estoque')->after('shipping_info');
            }
            
            // Variações do produto (JSON)
            if (!Schema::hasColumn('products', 'variations')) {
                $table->json('variations')->nullable()->comment('Variações do produto (cor, tamanho, etc.)')->after('warehouse_location');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $columnsToRemove = [
                'shipping_info', 'warehouse_location', 'variations'
            ];
            
            foreach ($columnsToRemove as $column) {
                if (Schema::hasColumn('products', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};