<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('financial_categories')) {
            return;
        }

        Schema::table('financial_categories', function (Blueprint $table) {
            if (!Schema::hasColumn('financial_categories', 'applies_to_payable')) {
                $table->boolean('applies_to_payable')->default(false)->after('type');
            }
            if (!Schema::hasColumn('financial_categories', 'applies_to_receivable')) {
                $table->boolean('applies_to_receivable')->default(false)->after('applies_to_payable');
            }
        });

        DB::table('financial_categories')
            ->where('type', 'receita')
            ->update(['applies_to_receivable' => true]);

        DB::table('financial_categories')
            ->where('type', 'despesa')
            ->update(['applies_to_payable' => true]);

        if (DB::getDriverName() === 'mysql' && Schema::hasColumn('financial_categories', 'type')) {
            DB::statement(
                "ALTER TABLE financial_categories MODIFY type ENUM('receita', 'despesa', 'ambos') NOT NULL"
            );
        }

        if (DB::getDriverName() === 'mysql') {
            $indexes = collect(DB::select('SHOW INDEX FROM financial_categories'))
                ->pluck('Key_name')
                ->unique();

            if ($indexes->contains('financial_categories_tenant_name_type_unique')) {
                Schema::table('financial_categories', function (Blueprint $table) {
                    $table->dropUnique('financial_categories_tenant_name_type_unique');
                });
            }

            if (!$indexes->contains('financial_categories_tenant_name_unique')) {
                Schema::table('financial_categories', function (Blueprint $table) {
                    $table->unique(['tenant_id', 'name'], 'financial_categories_tenant_name_unique');
                });
            }
        }
    }

    public function down(): void
    {
        if (!Schema::hasTable('financial_categories')) {
            return;
        }

        Schema::table('financial_categories', function (Blueprint $table) {
            if (Schema::hasColumn('financial_categories', 'applies_to_payable')) {
                $table->dropColumn('applies_to_payable');
            }
            if (Schema::hasColumn('financial_categories', 'applies_to_receivable')) {
                $table->dropColumn('applies_to_receivable');
            }
        });
    }
};
