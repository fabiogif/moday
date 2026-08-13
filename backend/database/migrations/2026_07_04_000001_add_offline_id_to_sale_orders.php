<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('sale_orders', 'offline_id')) {
            Schema::table('sale_orders', function (Blueprint $table) {
                $table->string('offline_id', 100)->nullable()->after('uuid');
                $table->unique(['tenant_id', 'offline_id'], 'sale_orders_tenant_offline_id_unique');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('sale_orders', 'offline_id')) {
            Schema::table('sale_orders', function (Blueprint $table) {
                $table->dropUnique('sale_orders_tenant_offline_id_unique');
                $table->dropColumn('offline_id');
            });
        }
    }
};
