<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->string('delivery_token', 64)->nullable()->unique()->after('pod_reference');
        });

        Schema::table('shipment_sale_order', function (Blueprint $table) {
            $table->string('pod_photo_path')->nullable();
            $table->string('pod_signature_path')->nullable();
            $table->timestamp('pod_delivered_at')->nullable();
            $table->string('pod_recipient_name')->nullable();
            $table->decimal('pod_latitude', 10, 7)->nullable();
            $table->decimal('pod_longitude', 10, 7)->nullable();
            $table->enum('pod_status', ['delivered', 'refused', 'partial'])->nullable();
            $table->text('pod_notes')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropColumn('delivery_token');
        });

        Schema::table('shipment_sale_order', function (Blueprint $table) {
            $table->dropColumn([
                'pod_photo_path', 'pod_signature_path', 'pod_delivered_at',
                'pod_recipient_name', 'pod_latitude', 'pod_longitude',
                'pod_status', 'pod_notes',
            ]);
        });
    }
};
