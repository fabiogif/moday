<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Global catalog of all features available in the system
        Schema::create('feature_definitions', function (Blueprint $table) {
            $table->id();
            $table->string('key')->unique();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('category');          // Estoque, Financeiro, Fiscal, Comercial, Analytics, Logística
            $table->string('icon')->nullable();
            $table->string('plan_tier')->default('professional'); // basic|professional|enterprise
            $table->integer('display_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Per-plan feature toggle
        Schema::create('plan_features', function (Blueprint $table) {
            $table->id();
            $table->foreignId('plan_id')->constrained('plans')->cascadeOnDelete();
            $table->string('feature_key');
            $table->boolean('is_enabled')->default(false);
            $table->string('display_name')->nullable();  // override display name if needed
            $table->timestamps();

            $table->unique(['plan_id', 'feature_key']);
            $table->foreign('feature_key')->references('key')->on('feature_definitions')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plan_features');
        Schema::dropIfExists('feature_definitions');
    }
};
