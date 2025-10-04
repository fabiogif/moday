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
        if (!Schema::hasTable('profiles')) {
            return;
        }

        Schema::table('profiles', function (Blueprint $table) {
            if (!Schema::hasColumn('profiles', 'tenant_id')) {
                $table->unsignedBigInteger('tenant_id')->nullable()->after('description');
                $table->foreign('tenant_id')->references('id')->on('tenants')->onDelete('set null');
            }

            if (!Schema::hasColumn('profiles', 'is_active')) {
                $table->boolean('is_active')->default(true)->after('tenant_id');
            }

            if (!Schema::hasColumn('profiles', 'deleted_at')) {
                $table->softDeletes();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('profiles')) {
            return;
        }

        Schema::table('profiles', function (Blueprint $table) {
            if (Schema::hasColumn('profiles', 'tenant_id')) {
                $table->dropForeign(['tenant_id']);
                $table->dropColumn('tenant_id');
            }

            if (Schema::hasColumn('profiles', 'is_active')) {
                $table->dropColumn('is_active');
            }

            if (Schema::hasColumn('profiles', 'deleted_at')) {
                $table->dropSoftDeletes();
            }
        });
    }
};


