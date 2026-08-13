<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('plan_migrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained('tenants')->onDelete('cascade');
            $table->foreignId('from_plan_id')->nullable()->constrained('plans')->onDelete('set null');
            $table->foreignId('to_plan_id')->constrained('plans')->onDelete('cascade');
            $table->string('status')->default('completed'); // completed, pending_payment, cancelled
            $table->timestamp('migrated_at');
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->index(['tenant_id', 'migrated_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plan_migrations');
    }
};
