<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('suppliers')) {
            return;
        }

        try {
            Schema::table('suppliers', function (Blueprint $table) {
                $table->index(['tenant_id', 'name'], 'suppliers_tenant_name_index');
            });
        } catch (\Throwable) {
            // Índice já existe
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('suppliers')) {
            return;
        }

        try {
            Schema::table('suppliers', function (Blueprint $table) {
                $table->dropIndex('suppliers_tenant_name_index');
            });
        } catch (\Throwable) {
            // no-op
        }
    }
};
