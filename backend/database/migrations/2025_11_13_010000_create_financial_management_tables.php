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
        if (!Schema::hasTable('financial_categories')) {
            Schema::create('financial_categories', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->uuid('uuid')->unique();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->enum('type', ['receita', 'despesa']);
                $table->string('description')->nullable();
                $table->string('color', 7)->default('#3b82f6');
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->softDeletes();

                $table->unique(['tenant_id', 'name', 'type'], 'financial_categories_tenant_name_type_unique');
                $table->index(['tenant_id', 'type'], 'financial_categories_tenant_type_index');
                $table->index('is_active');
            });
        }

        if (!Schema::hasTable('payment_methods')) {
            Schema::create('payment_methods', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->uuid('uuid')->unique();
                $table->string('name');
                $table->string('type')->nullable();
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->timestamps();

                $table->index(['tenant_id', 'is_active'], 'payment_methods_tenant_active_index');
                $table->index('type');
            });
        }

        if (!Schema::hasTable('suppliers')) {
            Schema::create('suppliers', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->uuid('uuid')->unique();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->string('name');
                $table->string('fantasy_name')->nullable();
                $table->string('document');
                $table->enum('document_type', ['cpf', 'cnpj'])->default('cnpj');
                $table->string('email')->nullable();
                $table->string('phone');
                $table->string('phone2')->nullable();
                $table->string('address')->nullable();
                $table->string('number')->nullable();
                $table->string('complement')->nullable();
                $table->string('neighborhood')->nullable();
                $table->string('city')->nullable();
                $table->string('state', 2)->nullable();
                $table->string('zip_code', 9)->nullable();
                $table->string('bank_name')->nullable();
                $table->string('bank_agency')->nullable();
                $table->string('bank_account')->nullable();
                $table->string('pix_key')->nullable();
                $table->text('notes')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->softDeletes();

                $table->unique('document');
                $table->index(['tenant_id', 'is_active'], 'suppliers_tenant_active_index');
                $table->index('document');
                $table->index('name');
            });
        }

        if (!Schema::hasTable('expenses')) {
            Schema::create('expenses', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->uuid('uuid')->unique();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->foreignId('financial_category_id')->nullable()->constrained('financial_categories')->nullOnDelete();
                $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
                $table->uuid('payment_method_id')->nullable();
                $table->string('description');
                $table->date('issue_date');
                $table->date('due_date');
                $table->date('payment_date')->nullable();
                $table->decimal('amount', 10, 2);
                $table->enum('status', ['pendente', 'pago', 'cancelado'])->default('pendente');
                $table->string('recurrence')->nullable();
                $table->text('notes')->nullable();
                $table->string('attachment_path')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['tenant_id', 'status'], 'expenses_tenant_status_index');
                $table->index(['tenant_id', 'due_date'], 'expenses_tenant_due_index');
                $table->index('payment_method_id');

                $table->foreign('payment_method_id')->references('uuid')->on('payment_methods')->nullOnDelete();
            });
        }

        if (!Schema::hasTable('accounts_payable')) {
            Schema::create('accounts_payable', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->uuid('uuid')->unique();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->foreignId('financial_category_id')->nullable()->constrained('financial_categories')->nullOnDelete();
                $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
                $table->uuid('payment_method_id')->nullable();
                $table->foreignId('expense_id')->nullable()->constrained('expenses')->nullOnDelete();
                $table->string('description');
                $table->date('issue_date');
                $table->date('due_date');
                $table->date('payment_date')->nullable();
                $table->decimal('amount', 10, 2);
                $table->decimal('amount_paid', 10, 2)->nullable();
                $table->decimal('discount', 10, 2)->nullable();
                $table->decimal('interest', 10, 2)->nullable();
                $table->decimal('fine', 10, 2)->nullable();
                $table->enum('status', ['pendente', 'pago', 'parcial', 'vencido', 'cancelado'])->default('pendente');
                $table->string('document_number')->nullable();
                $table->integer('installment_number')->nullable();
                $table->integer('total_installments')->nullable();
                $table->text('notes')->nullable();
                $table->string('attachment_path')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['tenant_id', 'status'], 'accounts_payable_tenant_status_index');
                $table->index(['tenant_id', 'due_date'], 'accounts_payable_tenant_due_index');
                $table->index('supplier_id');
                $table->index('document_number');
                $table->index('payment_method_id');

                $table->foreign('payment_method_id')->references('uuid')->on('payment_methods')->nullOnDelete();
            });
        }

        if (!Schema::hasTable('accounts_receivable')) {
            Schema::create('accounts_receivable', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->uuid('uuid')->unique();
                $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
                $table->foreignId('financial_category_id')->nullable()->constrained('financial_categories')->nullOnDelete();
                $table->foreignId('client_id')->nullable()->constrained('clients')->nullOnDelete();
                $table->foreignId('order_id')->nullable()->constrained('orders')->nullOnDelete();
                $table->uuid('payment_method_id')->nullable();
                $table->string('description');
                $table->date('issue_date');
                $table->date('due_date');
                $table->date('receipt_date')->nullable();
                $table->decimal('amount', 10, 2);
                $table->decimal('amount_received', 10, 2)->nullable();
                $table->decimal('discount', 10, 2)->nullable();
                $table->decimal('interest', 10, 2)->nullable();
                $table->decimal('fine', 10, 2)->nullable();
                $table->enum('status', ['pendente', 'recebido', 'parcial', 'vencido', 'cancelado'])->default('pendente');
                $table->string('document_number')->nullable();
                $table->integer('installment_number')->nullable();
                $table->integer('total_installments')->nullable();
                $table->text('notes')->nullable();
                $table->string('attachment_path')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->index(['tenant_id', 'status'], 'accounts_receivable_tenant_status_index');
                $table->index(['tenant_id', 'due_date'], 'accounts_receivable_tenant_due_index');
                $table->index('client_id');
                $table->index('order_id');
                $table->index('payment_method_id');

                $table->foreign('payment_method_id')->references('uuid')->on('payment_methods')->nullOnDelete();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounts_receivable');
        Schema::dropIfExists('accounts_payable');
        Schema::dropIfExists('expenses');
        Schema::dropIfExists('suppliers');
        Schema::dropIfExists('payment_methods');
        Schema::dropIfExists('financial_categories');
    }
};

