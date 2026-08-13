<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seasonal_alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            // expiring_lot | expired_lot | holiday_upcoming | promotion_suggestion | seasonal_peak
            $table->string('type', 50);
            $table->string('priority', 20)->default('normal'); // low | normal | high | critical
            $table->string('title', 255);
            $table->text('body')->nullable();
            $table->json('metadata')->nullable(); // {product_id, batch_id, holiday_id, days_until, etc.}
            $table->date('reference_date')->nullable();
            $table->timestamp('acknowledged_at')->nullable();
            $table->foreignId('acknowledged_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['tenant_id', 'type', 'acknowledged_at']);
            $table->index(['tenant_id', 'priority', 'acknowledged_at']);
            $table->index(['tenant_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seasonal_alerts');
    }
};
