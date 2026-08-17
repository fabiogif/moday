<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('price_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('changed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('field', 50)->default('price'); // price | price_cost | promotional_price | price_minimum
            $table->decimal('old_value', 12, 4)->nullable();
            $table->decimal('new_value', 12, 4);
            $table->decimal('change_pct', 6, 2)->nullable(); // percentage change
            $table->string('reason', 255)->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'product_id', 'created_at']);
            $table->index(['tenant_id', 'field', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('price_histories');
    }
};
