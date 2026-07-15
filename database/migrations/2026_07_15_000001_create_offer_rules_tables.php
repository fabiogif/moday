<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('offer_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('type', ['quantity_discount', 'combo', 'cross_sell']);
            $table->decimal('discount_percent', 5, 2)->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->integer('priority')->default(0);
            $table->timestamps();

            $table->index(['tenant_id', 'is_active', 'type']);
        });

        Schema::create('offer_rule_products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('offer_rule_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->enum('role', ['trigger', 'result']);
            $table->integer('min_quantity')->default(1);
            $table->timestamps();

            $table->index(['offer_rule_id', 'role']);
            $table->index(['product_id', 'role']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('offer_rule_products');
        Schema::dropIfExists('offer_rules');
    }
};
