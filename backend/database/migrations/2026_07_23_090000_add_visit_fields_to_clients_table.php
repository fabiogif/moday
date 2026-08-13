<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            if (!Schema::hasColumn('clients', 'latitude')) {
                $table->decimal('latitude', 10, 7)->nullable()->after('zip_code');
            }
            if (!Schema::hasColumn('clients', 'longitude')) {
                $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            }
            if (!Schema::hasColumn('clients', 'abc_classification')) {
                $table->enum('abc_classification', ['A', 'B', 'C'])->nullable()->after('longitude');
            }
            if (!Schema::hasColumn('clients', 'is_vip')) {
                $table->boolean('is_vip')->default(false)->after('abc_classification');
            }
            if (!Schema::hasColumn('clients', 'business_hours_start')) {
                $table->time('business_hours_start')->nullable()->after('is_vip');
            }
            if (!Schema::hasColumn('clients', 'business_hours_end')) {
                $table->time('business_hours_end')->nullable()->after('business_hours_start');
            }
            if (!Schema::hasColumn('clients', 'last_visit_at')) {
                $table->timestamp('last_visit_at')->nullable()->after('business_hours_end');
            }
        });

        try {
            Schema::table('clients', function (Blueprint $table) {
                $table->index(['tenant_id', 'abc_classification'], 'clients_tenant_abc_idx');
            });
        } catch (\Throwable) {
            // Índice já existe
        }

        try {
            Schema::table('clients', function (Blueprint $table) {
                $table->index(['tenant_id', 'is_vip'], 'clients_tenant_vip_idx');
            });
        } catch (\Throwable) {
            // Índice já existe
        }
    }

    public function down(): void
    {
        Schema::table('clients', function (Blueprint $table) {
            try {
                $table->dropIndex('clients_tenant_abc_idx');
            } catch (\Throwable) {
            }
            try {
                $table->dropIndex('clients_tenant_vip_idx');
            } catch (\Throwable) {
            }

            $columns = array_filter([
                Schema::hasColumn('clients', 'latitude') ? 'latitude' : null,
                Schema::hasColumn('clients', 'longitude') ? 'longitude' : null,
                Schema::hasColumn('clients', 'abc_classification') ? 'abc_classification' : null,
                Schema::hasColumn('clients', 'is_vip') ? 'is_vip' : null,
                Schema::hasColumn('clients', 'business_hours_start') ? 'business_hours_start' : null,
                Schema::hasColumn('clients', 'business_hours_end') ? 'business_hours_end' : null,
                Schema::hasColumn('clients', 'last_visit_at') ? 'last_visit_at' : null,
            ]);

            if ($columns !== []) {
                $table->dropColumn(array_values($columns));
            }
        });
    }
};
