<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('achievement_definitions')) {
            Schema::create('achievement_definitions', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->unsignedBigInteger('tenant_id')->nullable();
                $table->string('key', 100);
                $table->string('name');
                $table->text('description')->nullable();
                $table->string('icon', 100)->nullable();
                $table->string('badge_color', 30)->nullable();
                $table->string('category', 50);
                $table->string('trigger_type', 50);
                $table->json('trigger_config');
                $table->integer('points_reward')->default(0);
                $table->boolean('is_active')->default(true);
                $table->integer('display_order')->default(0);
                $table->timestamps();

                $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
                $table->index(['tenant_id', 'is_active']);
                $table->index('key');
                $table->index(['tenant_id', 'category']);
                $table->unique(['tenant_id', 'key']);
            });
        }

        if (!Schema::hasTable('user_achievements')) {
            Schema::create('user_achievements', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->unsignedBigInteger('tenant_id');
                $table->unsignedBigInteger('user_id');
                $table->unsignedBigInteger('achievement_definition_id');
                $table->timestamp('unlocked_at');
                $table->unsignedBigInteger('sale_order_id')->nullable();
                $table->json('metadata')->nullable();
                $table->timestamp('created_at')->nullable();

                $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
                $table->foreign('achievement_definition_id')->references('id')->on('achievement_definitions')->cascadeOnDelete();
                $table->foreign('sale_order_id')->references('id')->on('sale_orders')->nullOnDelete();
                $table->index(['tenant_id', 'user_id']);
                $table->unique(['user_id', 'achievement_definition_id']);
            });
        }

        if (!Schema::hasTable('gamification_profiles')) {
            Schema::create('gamification_profiles', function (Blueprint $table) {
                $table->id();
                $table->uuid('uuid')->unique();
                $table->unsignedBigInteger('tenant_id');
                $table->unsignedBigInteger('user_id');
                $table->integer('total_points')->default(0);
                $table->integer('current_streak_days')->default(0);
                $table->integer('best_streak_days')->default(0);
                $table->date('last_activity_date')->nullable();
                $table->integer('achievements_count')->default(0);
                $table->timestamps();

                $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
                $table->index('tenant_id');
                $table->index(['tenant_id', 'total_points']);
                $table->unique('user_id');
            });
        }

        if (!Schema::hasTable('gamification_point_logs')) {
            Schema::create('gamification_point_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id');
                $table->unsignedBigInteger('user_id');
                $table->integer('points');
                $table->integer('balance_after');
                $table->string('source_type', 50);
                $table->unsignedBigInteger('source_id')->nullable();
                $table->string('description')->nullable();
                $table->timestamp('created_at')->nullable();

                $table->foreign('tenant_id')->references('id')->on('tenants')->cascadeOnDelete();
                $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
                $table->index(['tenant_id', 'user_id']);
                $table->index(['user_id', 'created_at']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('gamification_point_logs');
        Schema::dropIfExists('gamification_profiles');
        Schema::dropIfExists('user_achievements');
        Schema::dropIfExists('achievement_definitions');
    }
};
