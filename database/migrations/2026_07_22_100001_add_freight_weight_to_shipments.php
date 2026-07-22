<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->decimal('freight_weight_amount', 12, 2)->nullable()->after('cost_per_delivery');
            $table->decimal('freight_weight_unit', 12, 4)->nullable()->after('freight_weight_amount');
            $table->string('freight_weight_charge_mode', 20)->nullable()->after('freight_weight_unit');
            $table->decimal('freight_weight_quantity', 12, 3)->nullable()->after('freight_weight_charge_mode');
            $table->json('freight_weight_breakdown')->nullable()->after('freight_weight_quantity');
        });
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropColumn([
                'freight_weight_amount',
                'freight_weight_unit',
                'freight_weight_charge_mode',
                'freight_weight_quantity',
                'freight_weight_breakdown',
            ]);
        });
    }
};
