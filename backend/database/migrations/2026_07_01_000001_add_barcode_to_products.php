<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('products', 'barcode')) {
            Schema::table('products', function (Blueprint $table) {
                $table->string('barcode', 255)->nullable()->after('sku');
                $table->index(['tenant_id', 'barcode'], 'products_tenant_barcode_index');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('products', 'barcode')) {
            Schema::table('products', function (Blueprint $table) {
                $table->dropIndex('products_tenant_barcode_index');
                $table->dropColumn('barcode');
            });
        }
    }
};
