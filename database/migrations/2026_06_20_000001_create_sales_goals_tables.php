<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('sales_goals')) {
            Schema::create('sales_goals', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->unsignedBigInteger('tenant_id');
                $table->string('title');
                $table->text('description')->nullable();
                $table->string('goal_type', 30);
                $table->unsignedBigInteger('target_user_id')->nullable();
                $table->unsignedBigInteger('target_profile_id')->nullable();
                $table->unsignedBigInteger('target_product_id')->nullable();
                $table->string('period_type', 20);
                $table->date('period_start');
                $table->date('period_end');
                $table->decimal('target_value', 15, 2);
                $table->decimal('current_value', 15, 2)->default(0);
                $table->decimal('completion_percent', 5, 2)->default(0);
                $table->string('status', 20)->default('active');
                $table->unsignedBigInteger('created_by')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
                $table->foreign('target_user_id')->references('id')->on('users')->nullOnDelete();
                $table->foreign('target_profile_id')->references('id')->on('profiles')->nullOnDelete();
                $table->foreign('target_product_id')->references('id')->on('products')->nullOnDelete();
                $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();

                $table->index('tenant_id');
                $table->index(['tenant_id', 'goal_type']);
                $table->index(['tenant_id', 'status']);
                $table->index(['tenant_id', 'period_start', 'period_end']);
                $table->index('target_user_id');
                $table->index('target_profile_id');
                $table->index('target_product_id');
            });
        }

        if (!Schema::hasTable('sales_goal_progress_logs')) {
            Schema::create('sales_goal_progress_logs', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->unsignedBigInteger('sales_goal_id');
                $table->unsignedBigInteger('sale_order_id')->nullable();
                $table->decimal('previous_value', 15, 2);
                $table->decimal('added_value', 15, 2);
                $table->decimal('new_value', 15, 2);
                $table->string('event_type', 30);
                $table->timestamp('created_at')->nullable();

                $table->foreign('sales_goal_id')->references('id')->on('sales_goals')->cascadeOnDelete();
                $table->foreign('sale_order_id')->references('id')->on('sale_orders')->nullOnDelete();
                $table->index('sales_goal_id');
                $table->index('sale_order_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_goal_progress_logs');
        Schema::dropIfExists('sales_goals');
    }
};
