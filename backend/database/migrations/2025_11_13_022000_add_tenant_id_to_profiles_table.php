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
        if (Schema::hasTable('profiles') && !Schema::hasColumn('profiles', 'tenant_id')) {
            Schema::table('profiles', function (Blueprint $table) {
                $table->foreignId('tenant_id')
                    ->nullable()
                    ->after('description')
                    ->constrained('tenants')
                    ->cascadeOnDelete();

                $table->index(['tenant_id', 'is_active'], 'profiles_tenant_active_index');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('profiles') && Schema::hasColumn('profiles', 'tenant_id')) {
            Schema::table('profiles', function (Blueprint $table) {
                $table->dropForeign(['tenant_id']);
                $table->dropIndex('profiles_tenant_active_index');
                $table->dropColumn('tenant_id');
            });
        }
    }
};

