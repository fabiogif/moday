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

        if (!Schema::hasColumn('sale_orders', 'delivery_method')) {
            Schema::table('sale_orders', function (Blueprint $table) {
                $table->string('delivery_method', 20)->default('entrega')->after('use_client_address');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('sale_orders') && Schema::hasColumn('sale_orders', 'delivery_method')) {
            Schema::table('sale_orders', function (Blueprint $table) {
                $table->dropColumn('delivery_method');
            });
        }
    }
};
