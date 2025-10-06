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
        // Remove status column if it exists
        if (Schema::hasColumn('payment_methods', 'status')) {
            Schema::table('payment_methods', function (Blueprint $table) {
                $table->dropColumn('status');
            });
        }
        
        // Remove flag column if it exists
        if (Schema::hasColumn('payment_methods', 'flag')) {
            Schema::table('payment_methods', function (Blueprint $table) {
                $table->dropColumn('flag');
            });
        }
        
        // Remove status index if it exists
        try {
            Schema::table('payment_methods', function (Blueprint $table) {
                $table->dropIndex(['tenant_id', 'status']);
            });
        } catch (\Exception $e) {
            // Index doesn't exist, continue
        }
        
        // Remove flag index if it exists
        try {
            Schema::table('payment_methods', function (Blueprint $table) {
                $table->dropIndex(['tenant_id', 'flag']);
            });
        } catch (\Exception $e) {
            // Index doesn't exist, continue
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Add status column back if it doesn't exist
        if (!Schema::hasColumn('payment_methods', 'status')) {
            Schema::table('payment_methods', function (Blueprint $table) {
                $table->string('status')->default('active');
            });
        }
        
        // Add flag column back if it doesn't exist
        if (!Schema::hasColumn('payment_methods', 'flag')) {
            Schema::table('payment_methods', function (Blueprint $table) {
                $table->string('flag')->nullable();
            });
        }
    }
};
