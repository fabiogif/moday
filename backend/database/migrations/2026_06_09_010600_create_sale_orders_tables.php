<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sale_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->uuid('uuid')->unique();
            $table->string('identify')->nullable();

            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();

            $table->string('status')->default('orcamento');
            // orcamento | aprovado | separacao | faturado | entregue | cancelado

            $table->string('type')->default('venda'); // venda | troca | consignacao

            // Valores
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('discount_amount', 10, 2)->default(0);
            $table->decimal('tax_amount', 10, 2)->default(0);
            $table->decimal('freight_amount', 10, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);

            // Pagamento
            $table->integer('payment_term_days')->default(0);
            $table->string('payment_method')->nullable();
            $table->date('due_date')->nullable();
            $table->json('split_payments')->nullable();

            // Entrega
            $table->json('shipping_address')->nullable();
            $table->string('shipping_city')->nullable();
            $table->string('shipping_state', 2)->nullable();
            $table->string('shipping_zipcode')->nullable();
            $table->date('estimated_delivery')->nullable();

            // Fiscal
            $table->string('nfe_number')->nullable();
            $table->string('nfe_series')->nullable();
            $table->string('nfe_key', 60)->nullable();
            $table->string('nfe_status')->nullable();
            $table->timestamp('nfe_issued_at')->nullable();

            // Timestamps de status
            $table->timestamp('ordered_at')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('billed_at')->nullable();
            $table->timestamp('delivered_at')->nullable();

            $table->text('notes')->nullable();
            $table->timestamp('archived_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'client_id']);
        });

        Schema::create('sale_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sale_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('batch_id')->nullable()->constrained('batches')->nullOnDelete();
            $table->string('item_type')->default('venda'); // venda | bonificacao | devolucao

            $table->decimal('quantity', 12, 3);
            $table->decimal('unit_price', 10, 4);
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('tax_amount', 10, 2)->default(0);
        });

        // Price Tables
        Schema::create('price_tables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('type')->default('padrao'); // padrao | cliente_especifico | canal | volume
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('price_table_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('price_table_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->decimal('price', 10, 4);
            $table->integer('min_quantity')->default(1);
            $table->timestamps();

            $table->unique(['price_table_id', 'product_id', 'min_quantity']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_table_items');
        Schema::dropIfExists('price_tables');
        Schema::dropIfExists('sale_order_items');
        Schema::dropIfExists('sale_orders');
    }
};
