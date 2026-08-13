<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_invoices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('plan_id')->nullable();
            $table->string('mp_subscription_id', 100)->nullable();
            $table->string('mp_payment_id', 100)->nullable();
            $table->string('invoice_number', 30)->unique();
            $table->string('plan_name', 100);
            $table->decimal('amount', 10, 2);
            $table->enum('status', ['pending', 'paid', 'failed', 'refunded', 'cancelled'])->default('pending');
            $table->string('payment_method', 50)->nullable();
            $table->date('billing_cycle_start')->nullable();
            $table->date('billing_cycle_end')->nullable();
            $table->date('due_date')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->json('mp_payload')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['tenant_id', 'created_at'], 'idx_si_tenant_created');
            $table->index(['tenant_id', 'status'], 'idx_si_tenant_status');
            $table->index('mp_payment_id', 'idx_si_mp_payment');
            $table->index('mp_subscription_id', 'idx_si_mp_subscription');

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('plan_id')->references('id')->on('plans')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_invoices');
    }
};
