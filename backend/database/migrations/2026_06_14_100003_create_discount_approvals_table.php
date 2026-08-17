<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discount_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sale_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('requested_discount_pct', 5, 2);  // discount applied
            $table->decimal('profile_max_pct', 5, 2)->default(0); // limit of the user's profile
            $table->decimal('discount_amount', 12, 2)->nullable(); // absolute value
            $table->string('status', 20)->default('pending'); // pending | approved | rejected
            $table->text('notes')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'sale_order_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discount_approvals');
    }
};
