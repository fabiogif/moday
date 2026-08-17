<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('shipments')) {
            return;
        }

        Schema::table('shipments', function (Blueprint $table) {
            if (!Schema::hasColumn('shipments', 'freight_weight_amount')) {
                $table->decimal('freight_weight_amount', 12, 2)->nullable()->after('cost_per_delivery');
            }
            if (!Schema::hasColumn('shipments', 'freight_weight_unit')) {
                $table->decimal('freight_weight_unit', 12, 4)->nullable()->after('freight_weight_amount');
            }
            if (!Schema::hasColumn('shipments', 'freight_weight_charge_mode')) {
                $table->string('freight_weight_charge_mode', 20)->nullable()->after('freight_weight_unit');
            }
            if (!Schema::hasColumn('shipments', 'freight_weight_quantity')) {
                $table->decimal('freight_weight_quantity', 12, 3)->nullable()->after('freight_weight_charge_mode');
            }
            if (!Schema::hasColumn('shipments', 'freight_weight_breakdown')) {
                $table->json('freight_weight_breakdown')->nullable()->after('freight_weight_quantity');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('shipments')) {
            return;
        }

        Schema::table('shipments', function (Blueprint $table) {
            $cols = array_values(array_filter([
                Schema::hasColumn('shipments', 'freight_weight_amount') ? 'freight_weight_amount' : null,
                Schema::hasColumn('shipments', 'freight_weight_unit') ? 'freight_weight_unit' : null,
                Schema::hasColumn('shipments', 'freight_weight_charge_mode') ? 'freight_weight_charge_mode' : null,
                Schema::hasColumn('shipments', 'freight_weight_quantity') ? 'freight_weight_quantity' : null,
                Schema::hasColumn('shipments', 'freight_weight_breakdown') ? 'freight_weight_breakdown' : null,
            ]));
            if ($cols !== []) {
                $table->dropColumn($cols);
            }
        });
    }
};
