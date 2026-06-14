<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->string('region', 100)->nullable()->after('route_name');
            $table->json('optimized_route')->nullable()->after('region'); // ordered stop sequence
            $table->decimal('estimated_km', 8, 2)->nullable()->after('optimized_route');
            $table->decimal('delivery_cost', 10, 2)->nullable()->after('estimated_km');
            $table->decimal('cost_per_delivery', 10, 2)->nullable()->after('delivery_cost');
        });

        Schema::table('shipment_sale_order', function (Blueprint $table) {
            $table->integer('delivery_sequence')->nullable()->after('sale_order_id');
            $table->time('delivery_window_start')->nullable()->after('delivery_sequence');
            $table->time('delivery_window_end')->nullable()->after('delivery_window_start');
            $table->string('delivery_zipcode', 9)->nullable()->after('delivery_window_end');
        });
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropColumn(['region', 'optimized_route', 'estimated_km', 'delivery_cost', 'cost_per_delivery']);
        });

        Schema::table('shipment_sale_order', function (Blueprint $table) {
            $table->dropColumn(['delivery_sequence', 'delivery_window_start', 'delivery_window_end', 'delivery_zipcode']);
        });
    }
};
