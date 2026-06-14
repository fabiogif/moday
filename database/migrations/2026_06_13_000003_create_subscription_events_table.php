<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('event_type', 60); // TRIAL_STARTED, PAYMENT_RECEIVED, UPGRADE, etc.
            $table->string('status_before', 30)->nullable();
            $table->string('status_after', 30)->nullable();
            $table->unsignedBigInteger('plan_id_before')->nullable();
            $table->unsignedBigInteger('plan_id_after')->nullable();
            $table->json('mp_payload')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();
            // No updated_at — immutable audit log

            $table->index(['tenant_id', 'created_at'], 'idx_se_tenant_created');
            $table->index('event_type', 'idx_se_event_type');
            $table->index(['tenant_id', 'event_type'], 'idx_se_tenant_event');

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_events');
    }
};
