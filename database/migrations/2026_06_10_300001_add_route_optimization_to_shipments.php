<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            if (!Schema::hasColumn('shipments', 'region')) {
                $table->string('region', 100)->nullable()->after('route_name');
            }
            if (!Schema::hasColumn('shipments', 'optimized_route')) {
                $table->json('optimized_route')->nullable()->after('region');
            }
            if (!Schema::hasColumn('shipments', 'estimated_km')) {
                $table->decimal('estimated_km', 8, 2)->nullable()->after('optimized_route');
            }
            if (!Schema::hasColumn('shipments', 'delivery_cost')) {
                $table->decimal('delivery_cost', 10, 2)->nullable()->after('estimated_km');
            }
            if (!Schema::hasColumn('shipments', 'cost_per_delivery')) {
                $table->decimal('cost_per_delivery', 10, 2)->nullable()->after('delivery_cost');
            }
        });

        Schema::table('shipment_sale_order', function (Blueprint $table) {
            if (!Schema::hasColumn('shipment_sale_order', 'delivery_sequence')) {
                $table->integer('delivery_sequence')->nullable()->after('sale_order_id');
            }
            if (!Schema::hasColumn('shipment_sale_order', 'delivery_window_start')) {
                $table->time('delivery_window_start')->nullable()->after('delivery_sequence');
            }
            if (!Schema::hasColumn('shipment_sale_order', 'delivery_window_end')) {
                $table->time('delivery_window_end')->nullable()->after('delivery_window_start');
            }
            if (!Schema::hasColumn('shipment_sale_order', 'delivery_zipcode')) {
                $table->string('delivery_zipcode', 9)->nullable()->after('delivery_window_end');
            }
        });
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $columns = array_filter([
                Schema::hasColumn('shipments', 'region') ? 'region' : null,
                Schema::hasColumn('shipments', 'optimized_route') ? 'optimized_route' : null,
                Schema::hasColumn('shipments', 'estimated_km') ? 'estimated_km' : null,
                Schema::hasColumn('shipments', 'delivery_cost') ? 'delivery_cost' : null,
                Schema::hasColumn('shipments', 'cost_per_delivery') ? 'cost_per_delivery' : null,
            ]);

            if ($columns !== []) {
                $table->dropColumn(array_values($columns));
            }
        });

        Schema::table('shipment_sale_order', function (Blueprint $table) {
            $columns = array_filter([
                Schema::hasColumn('shipment_sale_order', 'delivery_sequence') ? 'delivery_sequence' : null,
                Schema::hasColumn('shipment_sale_order', 'delivery_window_start') ? 'delivery_window_start' : null,
                Schema::hasColumn('shipment_sale_order', 'delivery_window_end') ? 'delivery_window_end' : null,
                Schema::hasColumn('shipment_sale_order', 'delivery_zipcode') ? 'delivery_zipcode' : null,
            ]);

            if ($columns !== []) {
                $table->dropColumn(array_values($columns));
            }
        });
    }
};
