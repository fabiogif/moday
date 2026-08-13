<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fiscal_webhook_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sale_order_id')->nullable()->constrained()->nullOnDelete();
            $table->string('provider')->nullable();
            $table->string('event_type')->nullable();
            $table->string('status')->nullable();
            $table->json('payload');
            $table->timestamps();

            $table->index(['tenant_id', 'sale_order_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fiscal_webhook_events');
    }
};
