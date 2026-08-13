<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'product_type'))
                $table->string('product_type')->default('outro');
            if (!Schema::hasColumn('products', 'requires_prescription'))
                $table->boolean('requires_prescription')->default(false);
            if (!Schema::hasColumn('products', 'controlled_substance'))
                $table->boolean('controlled_substance')->default(false);
            if (!Schema::hasColumn('products', 'storage_temperature'))
                $table->string('storage_temperature')->default('ambient');
            if (!Schema::hasColumn('products', 'price_minimum'))
                $table->decimal('price_minimum', 10, 2)->nullable();
            if (!Schema::hasColumn('products', 'tax_ncm'))
                $table->string('tax_ncm', 10)->nullable();
            if (!Schema::hasColumn('products', 'tax_cst'))
                $table->string('tax_cst', 10)->nullable();
            if (!Schema::hasColumn('products', 'tax_rate'))
                $table->decimal('tax_rate', 5, 2)->nullable();
            if (!Schema::hasColumn('products', 'unit_of_measure'))
                $table->string('unit_of_measure', 10)->default('un');
            if (!Schema::hasColumn('products', 'units_per_box'))
                $table->integer('units_per_box')->default(1);
            if (!Schema::hasColumn('products', 'min_stock'))
                $table->integer('min_stock')->default(0);
            if (!Schema::hasColumn('products', 'max_stock'))
                $table->integer('max_stock')->nullable();
            if (!Schema::hasColumn('products', 'reorder_point'))
                $table->integer('reorder_point')->default(0);
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $cols = [
                'product_type', 'requires_prescription', 'controlled_substance',
                'storage_temperature', 'price_minimum', 'tax_ncm', 'tax_cst',
                'tax_rate', 'unit_of_measure', 'units_per_box',
                'min_stock', 'max_stock', 'reorder_point',
            ];
            foreach ($cols as $col) {
                if (Schema::hasColumn('products', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
    }
};
