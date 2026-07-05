<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('loyalty_programs')) {
            Schema::create('loyalty_programs', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->unsignedBigInteger('tenant_id');
                $table->string('name');
                $table->text('description')->nullable();
                $table->boolean('is_active')->default(true);
                $table->decimal('points_per_currency', 8, 2)->default(1.00);
                $table->decimal('min_purchase_amount', 10, 2)->nullable();
                $table->decimal('max_points_per_purchase', 10, 2)->nullable();
                $table->unsignedInteger('points_expiry_days')->nullable();
                $table->json('excluded_categories')->nullable();
                $table->json('excluded_products')->nullable();
                $table->decimal('birthday_multiplier', 5, 2)->nullable();
                $table->json('special_day_multipliers')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
                $table->index('tenant_id');
            });
        }

        if (!Schema::hasTable('loyalty_rewards')) {
            Schema::create('loyalty_rewards', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->unsignedBigInteger('loyalty_program_id');
                $table->string('name');
                $table->text('description')->nullable();
                $table->string('type')->default('discount_fixed');
                $table->unsignedInteger('points_required');
                $table->decimal('discount_value', 10, 2)->nullable();
                $table->unsignedBigInteger('product_id')->nullable();
                $table->integer('stock_quantity')->nullable();
                $table->unsignedInteger('max_redemptions_per_user')->nullable();
                $table->unsignedInteger('validity_days')->nullable();
                $table->boolean('is_active')->default(true);
                $table->date('available_from')->nullable();
                $table->date('available_until')->nullable();
                $table->timestamps();
                $table->softDeletes();

                $table->foreign('loyalty_program_id')->references('id')->on('loyalty_programs')->cascadeOnDelete();
                $table->foreign('product_id')->references('id')->on('products')->nullOnDelete();
                $table->index('loyalty_program_id');
            });
        }

        if (!Schema::hasTable('loyalty_transactions')) {
            Schema::create('loyalty_transactions', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->unsignedBigInteger('loyalty_program_id');
                $table->unsignedBigInteger('client_id');
                $table->string('type');
                $table->integer('points');
                $table->integer('balance_after')->default(0);
                $table->unsignedBigInteger('order_id')->nullable();
                $table->unsignedBigInteger('loyalty_reward_id')->nullable();
                $table->decimal('purchase_amount', 10, 2)->nullable();
                $table->decimal('multiplier', 5, 2)->nullable();
                $table->string('description')->nullable();
                $table->date('expires_at')->nullable();
                $table->timestamps();

                $table->foreign('loyalty_program_id')->references('id')->on('loyalty_programs')->cascadeOnDelete();
                $table->foreign('client_id')->references('id')->on('clients')->cascadeOnDelete();
                $table->foreign('loyalty_reward_id')->references('id')->on('loyalty_rewards')->nullOnDelete();
                $table->index(['loyalty_program_id', 'client_id']);
                $table->index('client_id');
            });
        }

        if (!Schema::hasTable('loyalty_redemptions')) {
            Schema::create('loyalty_redemptions', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->unsignedBigInteger('loyalty_reward_id');
                $table->unsignedBigInteger('client_id');
                $table->unsignedBigInteger('loyalty_transaction_id')->nullable();
                $table->unsignedInteger('points_used');
                $table->string('status')->default('pending');
                $table->unsignedBigInteger('order_id')->nullable();
                $table->string('coupon_code')->nullable();
                $table->timestamp('redeemed_at')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->timestamps();

                $table->foreign('loyalty_reward_id')->references('id')->on('loyalty_rewards')->cascadeOnDelete();
                $table->foreign('client_id')->references('id')->on('clients')->cascadeOnDelete();
                $table->foreign('loyalty_transaction_id')->references('id')->on('loyalty_transactions')->nullOnDelete();
                $table->index('client_id');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('loyalty_redemptions');
        Schema::dropIfExists('loyalty_transactions');
        Schema::dropIfExists('loyalty_rewards');
        Schema::dropIfExists('loyalty_programs');
    }
};
