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
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();
            $table->string('status')->default('Recebido do iFood');
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->string('customer_name')->nullable();
            $table->string('customer_phone')->nullable();
            $table->json('delivery_address')->nullable();
            $table->json('raw_payload')->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamp('last_synced_at')->nullable();
            $table->timestamps();
            $table->index(['tenant_id', 'status'], 'ifood_orders_tenant_status_idx');
        });

        Schema::create('ifood_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ifood_order_id')->constrained('ifood_orders')->cascadeOnDelete();
            $table->string('external_item_id')->nullable();
            $table->string('sku')->nullable();
            $table->string('name');
            $table->unsignedInteger('quantity');
            $table->decimal('unit_price', 12, 2)->default(0);
            $table->decimal('total_price', 12, 2)->default(0);
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
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ifood_api_logs');
        Schema::dropIfExists('ifood_order_status_logs');
        Schema::dropIfExists('ifood_order_items');
        Schema::dropIfExists('ifood_orders');
        Schema::dropIfExists('ifood_api_tokens');
    }
};

