<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            // SQLite: drop the old enum column (with its CHECK constraint) and add a plain string column
            if (Schema::hasColumn('tenants', 'account_status')) {
                Schema::table('tenants', function (Blueprint $table) {
                    $table->dropColumn('account_status');
                });
            }
            Schema::table('tenants', function (Blueprint $table) {
                $table->string('account_status', 30)->default('trial');
            });
        } else {
            // MySQL/MariaDB: expand enum values in-place
            DB::statement("ALTER TABLE tenants MODIFY COLUMN account_status ENUM(
                'trial','active','pending','under_review','delinquent','suspended','cancelled','expired'
            ) NOT NULL DEFAULT 'trial'");
        }

        Schema::table('tenants', function (Blueprint $table) {
            if (!Schema::hasColumn('tenants', 'mp_customer_id')) {
                $table->string('mp_customer_id', 100)->nullable()->after('subscription_plan')
                    ->comment('Customer ID no Mercado Pago');
            }
            if (!Schema::hasColumn('tenants', 'mp_subscription_id')) {
                $table->string('mp_subscription_id', 100)->nullable()->after('mp_customer_id')
                    ->comment('Preapproval ID no Mercado Pago');
            }
            if (!Schema::hasColumn('tenants', 'mp_subscription_status')) {
                $table->string('mp_subscription_status', 50)->nullable()->after('mp_subscription_id');
            }
            if (!Schema::hasColumn('tenants', 'current_period_start')) {
                $table->date('current_period_start')->nullable()->after('mp_subscription_status');
            }
            if (!Schema::hasColumn('tenants', 'current_period_end')) {
                $table->date('current_period_end')->nullable()->after('current_period_start');
            }
            if (!Schema::hasColumn('tenants', 'next_billing_date')) {
                $table->date('next_billing_date')->nullable()->after('current_period_end');
            }
            if (!Schema::hasColumn('tenants', 'cancellation_requested_at')) {
                $table->timestamp('cancellation_requested_at')->nullable()->after('next_billing_date');
            }
            if (!Schema::hasColumn('tenants', 'cancelled_at')) {
                $table->timestamp('cancelled_at')->nullable()->after('cancellation_requested_at');
            }
            if (!Schema::hasColumn('tenants', 'scheduled_downgrade_plan_id')) {
                $table->unsignedBigInteger('scheduled_downgrade_plan_id')->nullable()
                    ->after('cancelled_at');
            }
            if (!Schema::hasColumn('tenants', 'scheduled_downgrade_at')) {
                $table->date('scheduled_downgrade_at')->nullable()->after('scheduled_downgrade_plan_id');
            }
            if (!Schema::hasColumn('tenants', 'scheduled_downgrade_attempts')) {
                $table->unsignedTinyInteger('scheduled_downgrade_attempts')->default(0)
                    ->after('scheduled_downgrade_at');
            }
            if (!Schema::hasColumn('tenants', 'dunning_started_at')) {
                $table->timestamp('dunning_started_at')->nullable()->after('scheduled_downgrade_attempts');
            }
            if (!Schema::hasColumn('tenants', 'dunning_day')) {
                $table->unsignedInteger('dunning_day')->default(0)->after('dunning_started_at');
            }
            if (!Schema::hasColumn('tenants', 'data_deletion_scheduled_at')) {
                $table->timestamp('data_deletion_scheduled_at')->nullable()->after('dunning_day');
            }
        });

        // MySQL-only: indexes and FK (SQLite does not support ALTER TABLE ADD INDEX)
        if (DB::getDriverName() !== 'sqlite') {
            try {
                DB::statement('ALTER TABLE tenants ADD INDEX idx_tenants_mp_subscription (mp_subscription_id)');
            } catch (\Exception) {}
            try {
                DB::statement('ALTER TABLE tenants ADD INDEX idx_tenants_next_billing (next_billing_date)');
            } catch (\Exception) {}
            try {
                DB::statement('ALTER TABLE tenants ADD INDEX idx_tenants_dunning (dunning_started_at, dunning_day)');
            } catch (\Exception) {}
            try {
                DB::statement('ALTER TABLE tenants ADD INDEX idx_tenants_account_status (account_status)');
            } catch (\Exception) {}
            try {
                DB::statement('ALTER TABLE tenants ADD CONSTRAINT fk_tenants_scheduled_downgrade FOREIGN KEY (scheduled_downgrade_plan_id) REFERENCES plans(id) ON DELETE SET NULL');
            } catch (\Exception) {}
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            try {
                DB::statement('ALTER TABLE tenants DROP FOREIGN KEY fk_tenants_scheduled_downgrade');
            } catch (\Exception) {}
        }

        Schema::table('tenants', function (Blueprint $table) {
            $columns = [
                'mp_customer_id', 'mp_subscription_id', 'mp_subscription_status',
                'current_period_start', 'current_period_end', 'next_billing_date',
                'cancellation_requested_at', 'cancelled_at', 'scheduled_downgrade_plan_id',
                'scheduled_downgrade_at', 'scheduled_downgrade_attempts',
                'dunning_started_at', 'dunning_day', 'data_deletion_scheduled_at',
            ];
            foreach ($columns as $col) {
                if (Schema::hasColumn('tenants', $col)) {
                    $table->dropColumn($col);
                }
            }
        });

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement("ALTER TABLE tenants MODIFY COLUMN account_status ENUM(
                'trial','active','expired','suspended'
            ) NOT NULL DEFAULT 'trial'");
        }
    }
};
