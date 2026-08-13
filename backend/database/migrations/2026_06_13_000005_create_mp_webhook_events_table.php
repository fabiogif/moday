<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mp_webhook_events', function (Blueprint $table) {
            $table->id();
            $table->string('mp_event_id', 100); // id from MP payload
            $table->string('topic', 50);        // preapproval, payment, etc.
            $table->json('payload');
            $table->enum('status', ['processed', 'failed', 'ignored'])->default('processed');
            $table->text('error_message')->nullable();
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->timestamp('processed_at')->useCurrent();

            $table->unique('mp_event_id', 'uq_mp_event_id');
            $table->index('tenant_id', 'idx_mpwe_tenant');
            $table->index('processed_at', 'idx_mpwe_processed_at');
            $table->index(['topic', 'status'], 'idx_mpwe_topic_status');

            $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mp_webhook_events');
    }
};
