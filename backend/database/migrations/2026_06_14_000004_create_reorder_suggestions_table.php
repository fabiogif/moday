<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reorder_suggestions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained('suppliers')->nullOnDelete();
            $table->decimal('daily_avg_sales', 10, 4)->default(0);
            $table->integer('days_of_coverage')->default(0);    // current coverage in days
            $table->integer('lead_time_days')->default(0);
            $table->integer('suggested_quantity')->default(0);
            $table->decimal('estimated_cost', 12, 2)->nullable();
            $table->string('urgency')->default('normal');       // critical|urgent|normal
            $table->timestamp('calculated_at');
            $table->boolean('is_dismissed')->default(false);
            $table->boolean('po_created')->default(false);
            $table->foreignId('purchase_order_id')->nullable()->constrained('purchase_orders')->nullOnDelete();
            $table->timestamps();

            $table->unique(['tenant_id', 'product_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reorder_suggestions');
    }
};
