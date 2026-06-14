<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ifood_orders', function (Blueprint $table) {
            $table->string('display_id')->nullable()->after('external_order_id');
            $table->string('order_type')->nullable()->after('status');
            $table->string('order_timing')->nullable()->after('order_type');
            $table->string('sales_channel')->nullable()->after('order_timing');
            $table->string('category')->nullable()->after('sales_channel');
            $table->timestamp('preparation_start_at')->nullable()->after('received_at');
            $table->boolean('is_test')->default(false)->after('preparation_start_at');
            $table->text('extra_info')->nullable()->after('is_test');
            $table->uuid('merchant_identifier')->nullable()->after('extra_info');
            $table->string('merchant_name')->nullable()->after('merchant_identifier');
            $table->string('customer_document_number')->nullable()->after('customer_phone');
            $table->unsignedInteger('customer_orders_count')->nullable()->after('customer_document_number');
            $table->string('customer_segmentation')->nullable()->after('customer_orders_count');
            $table->decimal('items_total', 12, 2)->nullable()->after('total_amount');
            $table->decimal('delivery_fee', 12, 2)->nullable()->after('items_total');
            $table->decimal('benefits_total', 12, 2)->nullable()->after('delivery_fee');
            $table->decimal('additional_fees_total', 12, 2)->nullable()->after('benefits_total');
            $table->json('benefits')->nullable()->after('additional_fees_total');
            $table->json('additional_fees')->nullable()->after('benefits');
            $table->json('payments')->nullable()->after('additional_fees');
            $table->json('picking')->nullable()->after('payments');
            $table->json('delivery')->nullable()->after('picking');
            $table->json('takeout')->nullable()->after('delivery');
            $table->json('dinein')->nullable()->after('takeout');
            $table->json('schedule')->nullable()->after('dinein');
            $table->json('additional_info')->nullable()->after('schedule');
        });

        Schema::table('ifood_order_items', function (Blueprint $table) {
            $table->integer('sequence')->nullable()->after('ifood_order_id');
            $table->uuid('unique_id')->nullable()->after('sequence');
            $table->string('external_code')->nullable()->after('unique_id');
            $table->string('ean')->nullable()->after('external_code');
            $table->string('unit')->nullable()->after('quantity');
            $table->decimal('price', 12, 2)->nullable()->after('unit_price');
            $table->decimal('options_price', 12, 2)->nullable()->after('total_price');
            $table->decimal('customization_price', 12, 2)->nullable()->after('options_price');
            $table->text('observations')->nullable()->after('customization_price');
            $table->json('options')->nullable()->after('observations');
            $table->json('scale_prices')->nullable()->after('options');
            $table->decimal('quantity_value', 12, 3)->nullable()->after('quantity');
        });
    }

    public function down(): void
    {
        Schema::table('ifood_order_items', function (Blueprint $table) {
            $table->dropColumn([
                'sequence',
                'unique_id',
                'external_code',
                'ean',
                'unit',
                'price',
                'options_price',
                'customization_price',
                'observations',
                'options',
                'scale_prices',
                'quantity_value',
            ]);
        });

        Schema::table('ifood_orders', function (Blueprint $table) {
            $table->dropColumn([
                'display_id',
                'order_type',
                'order_timing',
                'sales_channel',
                'category',
                'preparation_start_at',
                'is_test',
                'extra_info',
                'merchant_identifier',
                'merchant_name',
                'customer_document_number',
                'customer_orders_count',
                'customer_segmentation',
                'items_total',
                'delivery_fee',
                'benefits_total',
                'additional_fees_total',
                'benefits',
                'additional_fees',
                'payments',
                'picking',
                'delivery',
                'takeout',
                'dinein',
                'schedule',
                'additional_info',
            ]);
        });
    }
};

