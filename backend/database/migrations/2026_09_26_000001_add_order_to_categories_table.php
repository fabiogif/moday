<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('categories')) {
            return;
        }

        Schema::table('categories', function (Blueprint $table) {
            if (!Schema::hasColumn('categories', 'order')) {
                $table->integer('order')->default(0)->after('is_active');
            }
        });

        try {
            Schema::table('categories', function (Blueprint $table) {
                $table->index(['tenant_id', 'order'], 'categories_tenant_id_order_index');
            });
        } catch (\Throwable) {
            // Índice já existe
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('categories')) {
            return;
        }

        try {
            Schema::table('categories', function (Blueprint $table) {
                $table->dropIndex('categories_tenant_id_order_index');
            });
        } catch (\Throwable) {
            // no-op
        }

        Schema::table('categories', function (Blueprint $table) {
            if (Schema::hasColumn('categories', 'order')) {
                $table->dropColumn('order');
            }
        });
    }
};
