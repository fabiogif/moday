<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->boolean('is_delivery')->default(false)->after('status');
            $table->boolean('use_client_address')->default(false)->after('is_delivery');
            $table->string('delivery_address')->nullable()->after('use_client_address');
            $table->string('delivery_city')->nullable()->after('delivery_address');
            $table->string('delivery_state')->nullable()->after('delivery_city');
            $table->string('delivery_zip_code')->nullable()->after('delivery_state');
            $table->string('delivery_neighborhood')->nullable()->after('delivery_zip_code');
            $table->string('delivery_number')->nullable()->after('delivery_neighborhood');
            $table->string('delivery_complement')->nullable()->after('delivery_number');
            $table->text('delivery_notes')->nullable()->after('delivery_complement');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'is_delivery',
                'use_client_address',
                'delivery_address',
                'delivery_city',
                'delivery_state',
                'delivery_zip_code',
                'delivery_neighborhood',
                'delivery_number',
                'delivery_complement',
                'delivery_notes'
            ]);
        });
    }
};