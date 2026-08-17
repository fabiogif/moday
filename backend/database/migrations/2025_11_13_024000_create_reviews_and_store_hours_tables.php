<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('reviews')) {
            Schema::create('reviews', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->uuid('uuid')->unique();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->foreignId('order_id')->constrained('orders')->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->unsignedTinyInteger('rating')->comment('1-5 estrelas');
                $table->text('comment')->nullable();
                $table->string('customer_name')->nullable();
                $table->string('customer_email')->nullable();
                $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
                $table->foreignId('moderated_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('moderated_at')->nullable();
                $table->text('moderation_reason')->nullable();
                $table->boolean('is_featured')->default(false);
                $table->integer('helpful_count')->default(0);
                $table->timestamps();
                $table->softDeletes();

                $table->unique('order_id');
                $table->index(['tenant_id', 'status', 'created_at'], 'reviews_tenant_status_created_index');
                $table->index('rating');
                $table->index('is_featured');
            });
        }

        if (!Schema::hasTable('store_hours')) {
            Schema::create('store_hours', function (Blueprint $table) {
                $table->bigIncrements('id');
                $table->uuid('uuid')->unique();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->boolean('is_always_open')->default(false);
                $table->unsignedTinyInteger('day_of_week')->nullable();
                $table->enum('delivery_type', ['delivery', 'pickup', 'both'])->default('both');
                $table->time('start_time')->nullable();
                $table->time('end_time')->nullable();
                $table->time('start_time_2')->nullable();
                $table->time('end_time_2')->nullable();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
                $table->softDeletes();

                $table->index(['tenant_id', 'day_of_week'], 'store_hours_tenant_day_index');
                $table->index(['tenant_id', 'delivery_type'], 'store_hours_tenant_delivery_index');
                $table->index(['tenant_id', 'is_active'], 'store_hours_tenant_active_index');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('store_hours');
        Schema::dropIfExists('reviews');
    }
};

