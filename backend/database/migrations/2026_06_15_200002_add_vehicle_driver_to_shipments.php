<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->foreignId('vehicle_id')->nullable()->after('carrier_id')
                ->constrained('vehicles')->nullOnDelete();
            $table->foreignId('driver_id')->nullable()->after('vehicle_id')
                ->constrained('drivers')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropForeignIdFor(\App\Models\Vehicle::class);
            $table->dropForeignIdFor(\App\Models\Driver::class);
            $table->dropColumn(['vehicle_id', 'driver_id']);
        });
    }
};
