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
        Schema::table('tenants', function (Blueprint $table) {
            // Adiciona novos campos se não existirem
            if (!Schema::hasColumn('tenants', 'phone')) {
                $table->string('phone')->nullable()->after('email');
            }
            
            if (!Schema::hasColumn('tenants', 'address')) {
                $table->text('address')->nullable();
            }
            
            if (!Schema::hasColumn('tenants', 'city')) {
                $table->string('city')->nullable();
            }
            
            if (!Schema::hasColumn('tenants', 'state')) {
                $table->string('state', 2)->nullable();
            }
            
            if (!Schema::hasColumn('tenants', 'zipcode')) {
                $table->string('zipcode')->nullable();
            }
            
            if (!Schema::hasColumn('tenants', 'country')) {
                $table->string('country', 2)->default('BR');
            }
            
            if (!Schema::hasColumn('tenants', 'is_active')) {
                $table->boolean('is_active')->default(true);
            }
            
            if (!Schema::hasColumn('tenants', 'slug')) {
                $table->string('slug')->nullable()->after('name');
            }
            
            if (!Schema::hasColumn('tenants', 'settings')) {
                $table->json('settings')->nullable();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            // Remove colunas adicionadas (não remove email pois já existia)
            $columnsToRemove = [];
            
            if (Schema::hasColumn('tenants', 'phone')) {
                $columnsToRemove[] = 'phone';
            }
            if (Schema::hasColumn('tenants', 'address')) {
                $columnsToRemove[] = 'address';
            }
            if (Schema::hasColumn('tenants', 'city')) {
                $columnsToRemove[] = 'city';
            }
            if (Schema::hasColumn('tenants', 'state')) {
                $columnsToRemove[] = 'state';
            }
            if (Schema::hasColumn('tenants', 'zipcode')) {
                $columnsToRemove[] = 'zipcode';
            }
            if (Schema::hasColumn('tenants', 'country')) {
                $columnsToRemove[] = 'country';
            }
            if (Schema::hasColumn('tenants', 'is_active')) {
                $columnsToRemove[] = 'is_active';
            }
            if (Schema::hasColumn('tenants', 'slug')) {
                $columnsToRemove[] = 'slug';
            }
            if (Schema::hasColumn('tenants', 'settings')) {
                $columnsToRemove[] = 'settings';
            }
            
            if (!empty($columnsToRemove)) {
                $table->dropColumn($columnsToRemove);
            }
        });
    }
};
