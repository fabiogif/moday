<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->decimal('total_weight_kg', 10, 3)->nullable()->after('cost_per_delivery');
            $table->decimal('total_volume_m3', 10, 4)->nullable()->after('total_weight_kg');
        });

        Schema::create('shipment_occurrences', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained('shipments')->cascadeOnDelete();
            $table->foreignId('sale_order_id')->nullable()->constrained('sale_orders')->nullOnDelete();
            $table->enum('type', ['delay', 'damage', 'refused', 'absent', 'other']);
            $table->text('description');
            $table->timestamp('occurred_at');
            $table->unsignedBigInteger('created_by');
            $table->timestamps();

            $table->index('shipment_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipment_occurrences');
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropColumn(['total_weight_kg', 'total_volume_m3']);
        });
    }
};
