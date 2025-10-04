<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Drop foreign key and column category_id from products if exists
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'category_id')) {
                // Drop foreign key safely if it exists
                try {
                    $table->dropForeign(['category_id']);
                } catch (\Throwable $e) {
                    // ignore if FK name differs or doesn't exist
                }
                $table->dropColumn('category_id');
            }
        });

        // Add unique constraint to pivot table if it doesn't exist
        try {
            // Check if unique constraint already exists
            $constraints = DB::select("
                SELECT CONSTRAINT_NAME 
                FROM information_schema.TABLE_CONSTRAINTS 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'category_product' 
                AND CONSTRAINT_TYPE = 'UNIQUE'
            ");
            
            if (empty($constraints)) {
                Schema::table('category_product', function (Blueprint $table) {
                    $table->unique(['product_id', 'category_id'], 'category_product_unique');
                });
            }
        } catch (\Throwable $e) {
            // Log but don't fail migration
            Log::warning("Could not add unique constraint: " . $e->getMessage());
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Recreate category_id (nullable) on products to rollback
        Schema::table('products', function (Blueprint $table) {
            if (!Schema::hasColumn('products', 'category_id')) {
                $table->unsignedBigInteger('category_id')->nullable()->after('description');
                // Best-effort: re-link FK
                try {
                    $table->foreign('category_id')->references('id')->on('categories')->onDelete('set null');
                } catch (\Throwable $e) {
                    // ignore
                }
            }
        });
    }
};


