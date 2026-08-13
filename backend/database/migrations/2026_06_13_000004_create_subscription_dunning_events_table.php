<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('subscription_dunning_events', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedTinyInteger('dunning_day'); // 0, 3, 5, 7, 30, 90, 120
            $table->enum('channel', ['email', 'in_app', 'status_change']);
            $table->boolean('success')->default(true);
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->useCurrent();

            // Idempotency: one record per tenant+day+channel
            $table->unique(['tenant_id', 'dunning_day', 'channel'], 'uq_dunning_tenant_day_channel');
            $table->index('tenant_id', 'idx_dunning_tenant');

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_dunning_events');
    }
};
