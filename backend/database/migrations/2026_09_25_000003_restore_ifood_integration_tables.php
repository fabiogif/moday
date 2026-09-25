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
        Schema::create('ifood_api_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->text('access_token');
            $table->text('refresh_token')->nullable();
            $table->string('token_type')->default('Bearer');
            $table->string('scope')->nullable();
            $table->timestamp('expires_at');
            $table->json('metadata')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['tenant_id', 'is_active', 'expires_at'], 'ifood_tokens_tenant_active_idx');
        });

        Schema::create('ifood_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('external_order_id')->unique();
            $table->string('display_id')->nullable();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default('Recebido do iFood');
            $table->string('order_type')->nullable();
            $table->string('order_timing')->nullable();
            $table->string('sales_channel')->nullable();
            $table->string('category')->nullable();
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->decimal('items_total', 12, 2)->nullable();
            $table->decimal('delivery_fee', 12, 2)->nullable();
            $table->decimal('benefits_total', 12, 2)->nullable();
            $table->decimal('additional_fees_total', 12, 2)->nullable();
            $table->string('customer_name')->nullable();
            $table->string('customer_phone')->nullable();
            $table->string('customer_document_number')->nullable();
            $table->unsignedInteger('customer_orders_count')->nullable();
            $table->string('customer_segmentation')->nullable();
            $table->json('delivery_address')->nullable();
            $table->json('benefits')->nullable();
            $table->json('additional_fees')->nullable();
            $table->json('payments')->nullable();
            $table->json('picking')->nullable();
            $table->json('delivery')->nullable();
            $table->json('takeout')->nullable();
            $table->json('dinein')->nullable();
            $table->json('schedule')->nullable();
            $table->json('additional_info')->nullable();
            $table->json('raw_payload')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamp('preparation_start_at')->nullable();
            $table->boolean('is_test')->default(false);
            $table->text('extra_info')->nullable();
            $table->uuid('merchant_identifier')->nullable();
            $table->string('merchant_name')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'status'], 'ifood_orders_tenant_status_idx');
        });

        Schema::create('ifood_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ifood_order_id')->constrained('ifood_orders')->cascadeOnDelete();
            $table->integer('sequence')->nullable();
            $table->uuid('unique_id')->nullable();
            $table->string('external_item_id')->nullable();
            $table->string('external_code')->nullable();
            $table->string('ean')->nullable();
            $table->string('sku')->nullable();
            $table->string('name');
            $table->unsignedInteger('quantity');
            $table->decimal('quantity_value', 12, 3)->nullable();
            $table->string('unit')->nullable();
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->decimal('price', 12, 2)->nullable();
            $table->decimal('total_price', 12, 2)->default(0);
            $table->decimal('options_price', 12, 2)->default(0);
            $table->decimal('customization_price', 12, 2)->default(0);
            $table->decimal('additional_fees_total', 12, 2)->default(0);
            $table->text('observations')->nullable();
            $table->json('options')->nullable();
            $table->json('scale_prices')->nullable();
            $table->json('additional_fees')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
        });

        Schema::create('ifood_order_status_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ifood_order_id')->constrained('ifood_orders')->cascadeOnDelete();
            $table->string('status');
            $table->timestamp('sent_to_ifood_at')->nullable();
            $table->integer('response_code')->nullable();
            $table->json('response_body')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
            $table->index(['ifood_order_id', 'status'], 'ifood_order_status_idx');
        });

        Schema::create('ifood_api_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('level')->default('info');
            $table->string('context')->nullable();
            $table->string('action')->nullable();
            $table->json('payload')->nullable();
            $table->text('message');
            $table->timestamps();
            $table->index(['tenant_id', 'level'], 'ifood_logs_tenant_level_idx');
        });

        Schema::create('ifood_oauth_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('user_code');
            $table->text('authorization_code_verifier');
            $table->string('verification_url');
            $table->string('verification_url_complete');
            $table->timestamp('expires_at');
            $table->enum('status', ['pending', 'completed'])->default('pending');
            $table->timestamps();

            $table->unique(['tenant_id', 'user_code']);
            $table->index(['tenant_id', 'status']);
            $table->index('expires_at');
        });

        Schema::create('ifood_catalog_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('merchant_id');
            $table->string('catalog_id')->nullable();
            $table->string('group_id')->nullable();
            $table->string('snapshot_type');
            $table->json('payload');
            $table->timestamp('captured_at')->useCurrent();
            $table->timestamps();

            $table->index(['tenant_id', 'snapshot_type']);
            $table->index(['tenant_id', 'catalog_id']);
            $table->index(['tenant_id', 'group_id']);
            $table->index('captured_at');
        });

        Schema::create('ifood_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('event_id')->index();
            $table->string('event_type')->nullable();
            $table->string('status')->default('pending')->index();
            $table->json('payload');
            $table->timestamp('received_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
            $table->unique(['tenant_id', 'event_id'], 'ifood_events_tenant_event_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ifood_events');
        Schema::dropIfExists('ifood_catalog_snapshots');
        Schema::dropIfExists('ifood_oauth_sessions');
        Schema::dropIfExists('ifood_api_logs');
        Schema::dropIfExists('ifood_order_status_logs');
        Schema::dropIfExists('ifood_order_items');
        Schema::dropIfExists('ifood_orders');
        Schema::dropIfExists('ifood_api_tokens');
    }
};
