<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('profiles')) {
            return;
        }

        Schema::table('profiles', function (Blueprint $table) {
            if (Schema::hasColumn('profiles', 'tenant_id')) {
                $table->dropUnique('profiles_name_unique');
                $table->unique(['tenant_id', 'name'], 'profiles_tenant_name_unique');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('profiles')) {
            return;
        }

        Schema::table('profiles', function (Blueprint $table) {
            $table->dropUnique('profiles_tenant_name_unique');
            $table->unique('name', 'profiles_name_unique');
        });
    }
};
