<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            if (!Schema::hasColumn('tenants', 'trade_name')) {
                $table->string('trade_name')->nullable()->after('name');
            }
            if (!Schema::hasColumn('tenants', 'state_registration')) {
                $table->string('state_registration')->nullable()->after('cnpj');
            }
            if (!Schema::hasColumn('tenants', 'company_type')) {
                $table->string('company_type')->default('ltda')->after('state_registration');
            }
            if (!Schema::hasColumn('tenants', 'segment')) {
                $table->string('segment')->default('geral')->after('company_type');
            }
            if (!Schema::hasColumn('tenants', 'nfe_series')) {
                $table->string('nfe_series')->default('1');
            }
            if (!Schema::hasColumn('tenants', 'nfe_next_number')) {
                $table->integer('nfe_next_number')->default(1);
            }
            if (!Schema::hasColumn('tenants', 'certificate_path')) {
                $table->string('certificate_path')->nullable();
            }
            if (!Schema::hasColumn('tenants', 'certificate_exp')) {
                $table->timestamp('certificate_exp')->nullable();
            }
        });

        Schema::table('plans', function (Blueprint $table) {
            if (!Schema::hasColumn('plans', 'has_order_completion_email')) {
                $table->boolean('has_order_completion_email')->default(false);
            }
            if (!Schema::hasColumn('plans', 'max_purchase_orders_per_month')) {
                $table->integer('max_purchase_orders_per_month')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('tenants', function (Blueprint $table) {
            $cols = ['trade_name', 'state_registration', 'company_type', 'segment',
                     'nfe_series', 'nfe_next_number', 'certificate_path', 'certificate_exp'];
            foreach ($cols as $col) {
                if (Schema::hasColumn('tenants', $col)) {
                    $table->dropColumn($col);
                }
            }
        });
        Schema::table('plans', function (Blueprint $table) {
            if (Schema::hasColumn('plans', 'max_purchase_orders_per_month')) {
                $table->dropColumn('max_purchase_orders_per_month');
            }
        });
    }
};
