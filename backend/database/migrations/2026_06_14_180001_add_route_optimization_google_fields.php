<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            if (!Schema::hasColumn('shipments', 'route_polyline')) {
                $table->text('route_polyline')->nullable()->after('optimized_route');
            }
            if (!Schema::hasColumn('shipments', 'estimated_duration_minutes')) {
                $table->unsignedInteger('estimated_duration_minutes')->nullable()->after('estimated_km');
            }
        });

        if (!Schema::hasTable('geocoded_addresses')) {
            Schema::create('geocoded_addresses', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->string('address_hash', 64);
                $table->string('formatted_address')->nullable();
                $table->decimal('latitude', 10, 7);
                $table->decimal('longitude', 10, 7);
                $table->string('source', 32)->default('google');
                $table->timestamp('geocoded_at')->nullable();
                $table->timestamps();

                $table->unique(['tenant_id', 'address_hash']);
            });
        }

        Schema::table('sale_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('sale_orders', 'shipping_latitude')) {
                $table->decimal('shipping_latitude', 10, 7)->nullable()->after('shipping_zipcode');
            }
            if (!Schema::hasColumn('sale_orders', 'shipping_longitude')) {
                $table->decimal('shipping_longitude', 10, 7)->nullable()->after('shipping_latitude');
            }
            if (!Schema::hasColumn('sale_orders', 'delivery_window_start')) {
                $table->time('delivery_window_start')->nullable()->after('estimated_delivery');
            }
            if (!Schema::hasColumn('sale_orders', 'delivery_window_end')) {
                $table->time('delivery_window_end')->nullable()->after('delivery_window_start');
            }
        });
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $columns = array_filter([
                Schema::hasColumn('shipments', 'route_polyline') ? 'route_polyline' : null,
                Schema::hasColumn('shipments', 'estimated_duration_minutes') ? 'estimated_duration_minutes' : null,
            ]);
            if ($columns !== []) {
                $table->dropColumn(array_values($columns));
            }
        });

        Schema::dropIfExists('geocoded_addresses');

        Schema::table('sale_orders', function (Blueprint $table) {
            $columns = array_filter([
                Schema::hasColumn('sale_orders', 'shipping_latitude') ? 'shipping_latitude' : null,
                Schema::hasColumn('sale_orders', 'shipping_longitude') ? 'shipping_longitude' : null,
                Schema::hasColumn('sale_orders', 'delivery_window_start') ? 'delivery_window_start' : null,
                Schema::hasColumn('sale_orders', 'delivery_window_end') ? 'delivery_window_end' : null,
            ]);
            if ($columns !== []) {
                $table->dropColumn(array_values($columns));
            }
        });
    }
};
