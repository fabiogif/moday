<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('carriers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->uuid('uuid')->unique();
            $table->string('identify')->nullable();
            $table->foreignId('carrier_id')->nullable()->constrained()->nullOnDelete();
            $table->string('route_name')->nullable();
            $table->string('status')->default('draft'); // draft | dispatched | delivered
            $table->string('driver_name')->nullable();
            $table->string('vehicle_plate')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->string('pod_reference')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('shipment_sale_order', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sale_order_id')->constrained()->cascadeOnDelete();
            $table->unique(['shipment_id', 'sale_order_id']);
        });

        Schema::create('inventory_counts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('open'); // open | closed
            $table->text('notes')->nullable();
            $table->foreignId('opened_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('closed_at')->nullable();
            $table->timestamps();
        });

        Schema::create('inventory_count_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('inventory_count_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('system_quantity', 12, 3)->default(0);
            $table->decimal('counted_quantity', 12, 3)->default(0);
            $table->decimal('variance', 12, 3)->default(0);
            $table->text('notes')->nullable();
        });

        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('action');
            $table->string('entity_type');
            $table->unsignedBigInteger('entity_id')->nullable();
            $table->json('payload')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'entity_type', 'entity_id']);
            $table->index(['tenant_id', 'created_at']);
        });

        Schema::table('sale_order_items', function (Blueprint $table) {
            if (!Schema::hasColumn('sale_order_items', 'quantity_picked')) {
                $table->decimal('quantity_picked', 12, 3)->default(0)->after('quantity');
            }
            if (!Schema::hasColumn('sale_order_items', 'picked_at')) {
                $table->timestamp('picked_at')->nullable()->after('quantity_picked');
            }
            if (!Schema::hasColumn('sale_order_items', 'picked_by')) {
                $table->foreignId('picked_by')->nullable()->after('picked_at')->constrained('users')->nullOnDelete();
            }
        });

        Schema::table('sale_orders', function (Blueprint $table) {
            if (!Schema::hasColumn('sale_orders', 'prescription_verified')) {
                $table->boolean('prescription_verified')->default(false)->after('type');
            }
        });

        Schema::table('tenants', function (Blueprint $table) {
            if (!Schema::hasColumn('tenants', 'fiscal_integration_provider')) {
                $table->string('fiscal_integration_provider')->nullable();
            }
            if (!Schema::hasColumn('tenants', 'fiscal_integration_config')) {
                $table->json('fiscal_integration_config')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('sale_orders', function (Blueprint $table) {
            if (Schema::hasColumn('sale_orders', 'prescription_verified')) {
                $table->dropColumn('prescription_verified');
            }
        });

        Schema::table('sale_order_items', function (Blueprint $table) {
            foreach (['picked_by', 'picked_at', 'quantity_picked'] as $col) {
                if (Schema::hasColumn('sale_order_items', $col)) {
                    if ($col === 'picked_by') {
                        $table->dropForeign(['picked_by']);
                    }
                    $table->dropColumn($col);
                }
            }
        });

        Schema::table('tenants', function (Blueprint $table) {
            foreach (['fiscal_integration_provider', 'fiscal_integration_config'] as $col) {
                if (Schema::hasColumn('tenants', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('inventory_count_items');
        Schema::dropIfExists('inventory_counts');
        Schema::dropIfExists('shipment_sale_order');
        Schema::dropIfExists('shipments');
        Schema::dropIfExists('carriers');
    }
};
